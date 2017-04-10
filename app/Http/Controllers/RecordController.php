<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateRecordForm;
use League\Flysystem\Exception;

use App\Image\ImageEditor;
use App\FieldArea;
use App\Record;
use App\RecordIssuerType;
use App\Template;

class RecordController extends Controller
{
    public static $record_issuer_types;

    public function __construct() {
        $this->middleware('auth');

        // TODO: extract this somewhere else (used in RecordIssuerController too!)
        // Create an assoc. array of id => type
        $record_issuer_types = RecordIssuerType::all();
        foreach ($record_issuer_types as $record_issuer_type) {
            self::$record_issuer_types[$record_issuer_type->id] = $record_issuer_type->type;
        }

    }

    public function show(Record $record) {
        $this->authorize('belongs_to_user', $record);

        // need to prepend 'app/' because Storage::url is stupid. It returns storage/ instead of storage/app/
        $url = storage_path('app/' . $record->path_to_file);

        return response()->file($url);
    }


    public function download(Record $record) {
        $this->authorize('belongs_to_user', $record);

        // need to prepend 'app/' because Storage::url is stupid. It returns storage/ instead of storage/app/
        $url = storage_path('app/' . $record->path_to_file);
        $url_parts = pathinfo($url);
        $file_name = "{$record->issuer->name}_{$record->issue_date->toDateString()}.{$url_parts['extension']}";

        return response()->download($url, $file_name);
    }

    public function destroy(Record $record) {
        $this->authorize('belongs_to_user', $record);

        // TODO: handle deletion failure
        $record->delete();

        return back();
    }

    public function edit(Record $record)
    {
        $this->authorize('belongs_to_user', $record);
        /*
         $is_issuer_type_bill is
         used to determine whether to hide bill related form controls in views.
         e.g only bills have due date but not bank statements.
         */
        $is_issuer_type_bill = $record->is_issuer_type_bill();
        return view('records.edit', compact('record', 'is_issuer_type_bill'));
    }

    // TODO: should redirect to record issuer page, not back to the edit page!
    public function update(UpdateRecordForm $request, Record $record)
    {
        // TODO: move this authorization policy to UpdateRecordForm instead
        $this->authorize('belongs_to_user', $record);
        // add Gate:: here, allow(some policy) if auth()-id() === post(id) : allow else deny
        $this->upload_file($request, $record);
        if ($request)
        {
            $record->update($request->all());
            session()->flash('success', 'Records updated.');
        } //call update only if there's changes
        return back();
    }

    // TODO: should delete old file if issue_date updated???
    // TODO: this is buggy -> it assumes that issue_date is present in the request
    private function upload_file($request, $record)
    {
        // upload only if user optionally uploaded a file
        if ($request ->file('record_file'))
        {
            $file          = $request->file('record_file');
            $extension     = $file->extension();
            $file_name     = "{$record->id}.{$extension}";
            $user_id = auth()->id();
            $record_issuer = $record->issuer;
            $path_to_store = "record_issuers/{$record_issuer->id}/records";

            return $path_of_uploaded_file = $file->storeAs($path_to_store, $file_name, ['visibility'=>'private']);
        }
        return null;
    }

    // TODO: add a policy!
    // TODO: clean up. You made it work. Now make it right
    public function show_extract_coords_page(Record $record) {
        // Determine field_area_inputs based on type first
        $is_bill = $record->issuer->type === RecordIssuerType::BILLORG_TYPE_ID;
        $field_area_names = ['issue_date', 'period', 'amount'];
        $field_area_attrs = ['page', 'x', 'y', 'w', 'h'];
        if ($is_bill) {
            $field_area_names = array_merge($field_area_names, ['due_date']);
        }

        // Fill with null by default
        $field_area_inputs = [];
        foreach ($field_area_names as $field_area_name) {
            foreach ($field_area_attrs as $attr) {
                $field_area_inputs["{$field_area_name}_{$attr}"] = null;
            }
        }

        // Check for existing template (record specific or record_issuer specific)
        $chosen_template = null;
        if ($record->template !== null) {
            $chosen_template = $record->template;
        } else if ($record->issuer->latest_template() !== null) {
            $chosen_template = $record->issuer->latest_template();

        }

        // Fill with existing field_area values if template exists
        if ($chosen_template !== null) {
            foreach ($field_area_names as $field_area_name) {
                $area_attr_name = $field_area_name . '_area';
                $field_area = $chosen_template->$area_attr_name;

                $record_page = $record->pages[$field_area->page];
                $page_geometry = ImageEditor::getImageGeometry(storage_path('app/' . $record_page->path));
                $field_area->x /= $page_geometry['width'];
                $field_area->w /= $page_geometry['width'];
                $field_area->y /= $page_geometry['height'];
                $field_area->h /= $page_geometry['height'];

                foreach ($field_area_attrs as $attr) {
                    $field_area_inputs["{$field_area_name}_{$attr}"] = $field_area->$attr;
                }
            }
        }

        $edit_value_mode = false;

        return view(
            'records.experimental_edit',
            compact('record', 'is_bill', 'field_area_inputs', 'edit_value_mode')
        );
    }

    // TODO: Should I store the coords as normalized coords in DB?
    // TODO: Warn user if duplicate record
    public function extract_coords(Record $record) {
        // Get the coords (and validate)
        // TODO: extract these long lists of validation to a specialized form handler and do typecasting
        // TODO: creation of many models should be inside a DB transaction to maintain integrity
        $field_area_names = ['issue_date', 'period', 'amount'];
        $field_area_attrs = ['page', 'x', 'y', 'w', 'h'];
        $is_bill = $record->issuer->type === RecordIssuerType::BILLORG_TYPE_ID;
        if ($is_bill) {
            $field_area_names = array_merge($field_area_names, ['due_date']);
        }

        $rules = [];
        foreach ($field_area_names as $field_area_name) {
            foreach($field_area_attrs as $attr) {
                $rules["{$field_area_name}_{$attr}"] = 'required';
            }
        }

        // Expect from client: issue_date_page, issue_date_x, ...
        $this->validate(request(), $rules);

        // Check if template exists -> compare the fields with existing one
        $chosen_template = null;
        if ($record->template !== null) {
            $chosen_template = $record->template;
        } else if ($record->issuer->latest_template() !== null) {
            $chosen_template = $record->issuer->latest_template();
        }

        $is_match = $chosen_template !== null; // true if all field area matches
        if ($chosen_template !== null) {
            $does_field_area_match = true;
            foreach ($field_area_names as $field_area_name) {
                $area_attr_name = $field_area_name . '_area';
                $field_area = $chosen_template->$area_attr_name;

                // TODO: Remove this cursed ugly, duplicated code
                // TODO: Move comparing coords to helper
                $record_page = $record->pages[$field_area->page];
                $page_geometry = ImageEditor::getImageGeometry(storage_path('app/' . $record_page->path));

                $page_match = $field_area->page === (int) request("{$field_area_name}_page");
                // allow +- 1 pixel deviation.
                // TODO: beautify
                $x_match = abs($field_area->x - ((double) request("{$field_area_name}_x")) * $page_geometry['width']) < 2;
                $y_match = abs($field_area->y - ((double) request("{$field_area_name}_y")) * $page_geometry['height']) < 2;
                $w_match = abs($field_area->w - ((double) request("{$field_area_name}_w")) * $page_geometry['width']) < 2;
                $h_match = abs($field_area->h - ((double) request("{$field_area_name}_h")) * $page_geometry['height']) < 2;

                $does_field_area_match = $page_match && $x_match && $y_match && $w_match && $h_match;

                if (!$does_field_area_match) {
                    $is_match = false;
                    break;
                }
            }
        }

        // $is_match is true only if template exists and matches the request data
        if ($is_match) {
            // if match template, point to that template
            $final_template = $chosen_template;
        } else {
            // Otherwise (whether template doens't exist or doesn't match), create a new template with field areas
            $template_data = [];
            foreach ($field_area_names as $field_area_name) {
                $field_area_data = [];

                $field_area_data['page'] = request("{$field_area_name}_page");
                $record_page = $record->pages[$field_area_data['page']];
                $page_geometry = ImageEditor::getImageGeometry(storage_path('app/' . $record_page->path));

                $field_area_data['x'] = (int) (request("{$field_area_name}_x") * $page_geometry['width']);
                $field_area_data['w'] = (int) ceil(request("{$field_area_name}_w") * $page_geometry['width']);
                $field_area_data['y'] = (int) (request("{$field_area_name}_y") * $page_geometry['height']);
                $field_area_data['h'] = (int) ceil(request("{$field_area_name}_h") * $page_geometry['height']);

                $template_data["{$field_area_name}_area_id"] = FieldArea::create($field_area_data)->id;
            }

            // TODO: Shouldn't just create a template like that from a temporary one (add a new attribute called active instead)
            $final_template = $record->issuer->create_template(
                new Template($template_data)
            );
        }

        // Set record to point to $template
        $record->update([
            'template_id' => $final_template->id
        ]);

        // Extract images by the coordinates and store it in temp dir
        // Interpret the texts using Tesseract, save the value
        // Delete the whole dir
        // TODO: copy and pasted from RecordIssuerController -- refactor this but make it work first!
        $user_id = auth()->id();
        $record_images_dir_path = "record_issuers/{$record->issuer->id}/records/" .
            "{$record->id}/img/";
        $cropped_dir_path = $record_images_dir_path . "cropped/";
        if(!Storage::exists($cropped_dir_path)) {
            Storage::makeDirectory($cropped_dir_path, 0777, true, true);
        }

        // TODO: Don't do OCR if matches record's template?
        $ocr_results = [];
        foreach ($field_area_names as $field_area_name) {
            $area_attr_name = $field_area_name . '_area';
            $field_area = $final_template->$area_attr_name;
            $crop_input_filename = storage_path('app/' . $record_images_dir_path . $field_area->page . ".jpg");
            $crop_output_filename = storage_path('app/' . $cropped_dir_path . $field_area_name . ".jpg");
            ImageEditor::cropJpeg(
                $crop_input_filename, $crop_output_filename,
                $field_area->x, $field_area->y, $field_area->w, $field_area->h
            );
            $ocr_results[$field_area_name] = ImageEditor::recognizeTextFromJpeg($crop_output_filename);
        }

        $record->update(array_merge($ocr_results, ['temporary' => false]));

        $field_area_inputs = request()->all();
        unset($field_area_inputs['_token']);
        $edit_value_mode = true;
        // Pass back to the same page, with coords and values filled
        return view(
            'records.experimental_edit',
            compact('record', 'is_bill', 'field_area_inputs', 'edit_value_mode')
        );

        // User has to confirm or edit the value field
        // Press OK
        // Pass to store_record_experimental, move the file to permanent place and also the pages
    }

    public function confirm_values(Record $record) {
        $is_bill = $record->issuer->type === RecordIssuerType::BILLORG_TYPE_ID;
        $field_area_names = ['issue_date', 'period', 'amount'];
        if ($is_bill) {
            $field_area_names = array_merge($field_area_names, ['due_date']);
        }

        foreach ($field_area_names as $field_area_name) {
            $rules[$field_area_name] = 'required';
        }

        $this->validate(request(), $rules);

        $record->update(request($field_area_names));

        // TODO: Need a mechanism to show user the temp records when they logged in

        return redirect()->route('show_record_issuer', $record->issuer);
    }
}

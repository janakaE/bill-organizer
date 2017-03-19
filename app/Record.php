<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


class Record extends Model
{

    /**
     * Cast attr => data_type only on get. This doesn't apply on set
     * @var array
     */
    protected $casts = [
        'period' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date'
    ];

    public $fillable = ['issue_date', 'due_date', 'period', 'amount', 'path_to_file',
        'record_issuer_id'];

    public function issuer() {
        return $this->belongsTo(RecordIssuer::class, 'record_issuer_id');
    }

    // return RecordIssuerType Object
    public function issuer_type()
    {
        return $this->issuer->issuer_type();
    }

    public function issuer_name()
    {
        return $this->issuer->name;
    }

    // return RecordIssuerType name in String
    public function issuer_type_name()
    {
        return $this->issuer_type->type;
    }

    public function is_issuer_type_bill()
    {
        return $this->issuer_type_name() === RecordIssuerType::BILLORG_TYPE_NAME;
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function setPeriodAttribute($value) {
        $this->attributes['period'] = Carbon::parse($value);
    }

    public function setIssueDateAttribute($value) {
        $this->attributes['issue_date'] = Carbon::parse($value);
    }

    public function setDueDateAttribute($value) {
        $this->attributes['due_date'] = Carbon::parse($value);
    }

    public function setAmountAttribute($value) {
        // trim $ if any
        $value = str_replace('$', '', $value);
        $this->attributes['amount'] = $value;
    }
}

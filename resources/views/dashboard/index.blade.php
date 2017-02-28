@extends('layouts.layout')

@section('content')
    <!--CONTENT-->
    <div class="ui main container" style="background:white; padding:90px 65px 65px 65px; min-height: 100vh;">

        <div class="ui fluid container">
            <div class="ui grid">
                <div class="sixteen wide column">
                    <div class="ui breadcrumb">
                        <!-- TODO: Extract breadcrumbs and add links-->
                        <span class="section">Home</span>
                        <i class="right angle icon divider"></i>
                        <span class="active section">Dashboard</span>
                    </div>
                </div>

                @if(count($user_record_issuers) === 0)
                    <div class="sixteen wide column">
                        <h1>Billing Organisations</h1>
                        <!--if no billing organisations in db-->
                        <div class="ui tiny message">
                            <p>There are no billing organisations yet - start by adding one below! (ﾉ^ヮ^)ﾉ*:・ﾟ✧</p>
                        </div>
                    </div>
                @endif

                @foreach($user_record_issuers as $record_issuer)
                    <div class="four wide column">
                        <div class="dotted-container">
                            <form method="POST" action="{{ url('/dashboard/record_issuers/' . $record_issuer->id) }}" style="display: inline;">
                                {{ csrf_field() }}
                                {{ method_field('DELETE') }}
                                <button type="submit" class="circular red ui icon right button">
                                    <i class="remove icon"></i>
                                </button>
                            </form>
                            <p><a href="{{ route('show_record_issuer', $record_issuer) }}">{{ $record_issuer->name }}</a></p>
                        </div>
                    </div>
                @endforeach

                <div class="four wide column">
                    <div class="dotted-container">
                        <button class="circular blue ui icon button" value="showModal"
                                onClick="$('.ui.modal.record-issuer').modal({onApprove: function() {
                                    $('form#add-record-issuer').submit();
                                }}).modal('show');">
                            <i class="icon plus"></i>
                        </button>
                        <p>Add New Billing Organisation</p>
                    </div>
                </div>
                <div class="four wide column"></div>
                <div class="four wide column"></div>
                <div class="four wide column"></div>
            </div>

            <div class="ui small record-issuer modal">
                <i class="close icon"></i>
                <div class="header">Add new record issuer</div>
                <div class="content">
                    <div class="ui fluid input">
                        <form method="POST" action="{{ url('/dashboard/record_issuers') }}"
                              class="ui form" id="add-record-issuer">
                            {{ csrf_field() }}
                            <div class="field">
                                <label for="name">
                                    Name:
                                </label>
                                <input id="name" type="text" name="name" placeholder="Enter record issuer name">
                            </div>
                            <div class="field">
                                <label for="type">
                                    Type
                                </label>
                                <select name="type" id="type">
                                    @foreach($record_issuer_types as $record_issuer_type)
                                        <option value="{{ $record_issuer_type->id }}">{{ $record_issuer_type->type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="actions">
                    <div class="ui button approve green" data-value="yes">Add</div>
                    <div class="ui button black cancel" data-value="no">Cancel</div>
                </div>
            </div>
        </div>
    </div>
@endsection
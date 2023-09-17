@extends('Admin/layouts/master')

@section('title') {{$setting->title}} | User Sales @endsection
@section('page_name') User Sales @endsection
@section('css')
    @include('layouts.loader.formLoader.loaderCss')
@endsection
@section('content')

    <div class="row">
        <div class="col-md-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{$setting->title}} Family Sales</h3>
                    <div class="">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <!--begin::Table-->
                        <table class="table table-striped table-bordered text-nowrap w-100" id="dataTable">
                            <thead>
                            <tr class="fw-bolder text-muted bg-light">
                                <th class="min-w-25px">#</th>
                                <th class="min-w-50px">Added By</th>
                                <th class="min-w-50px">ticket num</th>
                                <th class="min-w-50px">visit date</th>
                                <th class="min-w-50px">client</th>
                                <th class="min-w-50px">hours</th>
                                <th class="min-w-50px">visitors count</th>
                                <th class="min-w-50px">payment status</th>
                                <th class="min-w-50px">total price</th>
                                <th class="min-w-50px">payment method</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!--Delete MODAL -->
        <div class="modal fade" id="delete_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Delete Row</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input id="delete_id" name="id" type="hidden">
                        <p>Are You Sure Of Deleting This Row <span id="title" class="text-danger"></span>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" id="dismiss_delete_modal">
                            Back
                        </button>
                        <button type="button" class="btn btn-danger" id="delete_btn">Delete !</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- MODAL CLOSED -->

        <!-- Edit MODAL -->
        <div class="modal fade" id="editOrCreate" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content" id="modalContent">

                </div>
            </div>
        </div>
        <!-- Edit MODAL CLOSED -->
    </div>
    @include('Admin/layouts/myAjaxHelper')
@endsection
@section('ajaxCalls')
    <script>
        var loader = ` <div class="linear-background">
                            <div class="inter-crop"></div>
                            <div class="inter-right--top"></div>
                            <div class="inter-right--bottom"></div>
                        </div>
        `;

        var columns = [
            {data: 'id', name: 'id'},
            {data: 'add_by', name: 'add_by'},
            {data: 'ticket_num', name: 'ticket_num'},
            {data: 'visit_date', name: 'visit_date'},
            {data: 'client_id', name: 'client_id'},
            {data: 'hours_count', name: 'hours_count'},
            {data: 'visitors', name: 'visitors'},
            {data: 'payment_status', name: 'payment_status'},
            {data: 'total_price', name: 'total_price'},
            {data: 'payment_method', name: 'payment_method'},
        ]
        showData('{{route('sales.index')}}', columns);



    </script>
@endsection



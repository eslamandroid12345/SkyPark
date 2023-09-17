<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reference;
use App\Models\Reservations;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SaleController extends Controller
{
    public function index(request $request){
        if($request->ajax()) {
            $tickets = Ticket::latest()->get();
            return Datatables::of($tickets)
                ->editColumn('add_by', function ($tickets) {
                    return (User::where('id',$tickets->add_by)->first()->name) ?? '---';
                })
                ->editColumn('client_id', function ($tickets) {
                    return ($tickets->client->name) ?? '---';
                })
                ->editColumn('payment_status', function ($tickets) {
                    if ($tickets->payment_status == 0){
                        return '<span class="badge badge-danger">Not Paid</span>';
                    }
                    else{
                        return '<span class="badge badge-success">Paid</span>';
                    }
                })
                ->editColumn('visitors', function ($tickets) {
                    return ($tickets->models->count()) ?? '---';
                })
                ->escapeColumns([])
                ->make(true);
        }else{
            return view('Admin/sales/index');
        }
    }

    public function reservationSale(request $request){
        if($request->ajax()) {
            $reservations = Reservations::latest()->get();
            return Datatables::of($reservations)
                ->editColumn('add_by', function ($reservations) {
                    return (User::where('id',$reservations->add_by)->first()->name) ?? '---';
                })
                ->editColumn('client_id', function ($reservations) {
                    return ($reservations->client->name) ?? '---';
                })
                ->editColumn('visitors', function ($reservations) {
                    return ($reservations->models->count()) ?? '---';
                })
                ->escapeColumns([])
                ->make(true);
        }else{
            return view('Admin/sales/reservations');
        }
    }
}

<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Reservations;
use App\Models\Shifts;
use App\Models\Ticket;
use App\Models\TicketRevModel;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ExitController extends Controller
{

    function __construct()
    {

        $this->middleware('permission:Exit');

    }

    public function index(Request $request)
    {
        $returnArray = [];
        $ticket = [];
        $models = [];
        $customId = [];
        $topUpPrice = 0;
        $t2 = 0;
        $t1 = 0;
        $hours = 0;
        $type = '';
        $customId = '';
        $phone = '';
        $name = '';

        if ($request->has('search')) {

            $ticket = Ticket::whereDate('visit_date', date('Y-m-d'))
                ->where(function ($query_all)use ($request){
                    $query_all->WhereHas('client', function ($query) use ($request) {
                        $query->where('phone', $request->search);
                    })
                        ->orwhere('ticket_num', $request->search)
                        ->orWhereHas('in_models', function ($query) use ($request) {
                            $query->where('bracelet_number', $request->search);
                        });
                })
                ->with('in_models.type','client');

            if ($ticket->count() == 0) {
                $ticket = Reservations::whereDate('day', date('Y-m-d'))

                    ->where(function ($query)use($request){
                        $query->where('custom_id', $request->search)
                            ->orWhereHas('in_models', function ($query) use ($request) {
                                $query->where('bracelet_number', $request->search);
                            })
                            ->orWhere('phone', $request->search);
                    })


                    ->with('in_models.type');
                $type = 'rev';

            }else{
                $type = 'ticket';
            }



            $customId = $ticket->first()->ticket_num ?? $ticket->first()->custom_id ?? '';
            $phone = $ticket->first()->client->phone ?? $ticket->first()->phone??'';
            $name = $ticket->first()->client->name ?? $ticket->first()->client_name??'';


            $returnArray = [];

            if ($ticket->count() > 0) {

                foreach ($ticket->first()->in_models as $key => $model) {
                    $actions = view('sales.layouts.exit.actions', compact('model', 'key'));


                    $returnArray[$model->id] = "$actions";
                    $t1 = strtotime($model->shift_end);
                    $t2 = strtotime(date('H:i:s'));
                    // case there is no top up
                    if($model->shift_end == $model->shift_start)
                        $t1 = $t2;


                }
                $ticket = $ticket->first();
                $models = $ticket->in_models ?? [];


            }
        }
        // case there is top up then get the hours
        if ($t2 > $t1) {
            $hours = round(($t2 - $t1) / 3600);
        }

//        return $models;
        if ($request->has('search'))
            count($models) ? '' : toastr()->warning('there is no data');
        return view('sales.exit', compact('ticket', 'returnArray','name', 'type','models', 'customId','phone', 'hours'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $id = base64_decode($id);
        $model = TicketRevModel::findOrFail($id);
        $data['status'] = 'out';
        $model->update($data);

        if ($model->rev_id != '') {
            $ticket = Reservations::findOrFail($model->rev_id);
            $models = TicketRevModel::where('rev_id', $ticket->id)->where('status', 'in');
        }
        elseif($model->ticket_id != ''){
            $ticket = Ticket::findOrFail($model->ticket_id);
            $models = TicketRevModel::where('ticket_id', $ticket->id)->where('status', 'in');

        }else{
            toastr()->info('not found');
            return back();
        }


        if (!$models->count()) {
            $ticket->update($data);
        }

        toastr()->success('Group exit successfully');
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ticket_rev_model = TicketRevModel::findOrFail($id);
        return view('sales.layouts.exit.topup', compact('id'));

    }



    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'temp_status' => 'nullable|in:in,out',
            'top_up_hours' => 'nullable|numeric'
        ]);
        $model = TicketRevModel::findOrFail($id);
        $printUrl = '';

        if ($model->rev_id != '')
            $ticket = Reservations::findOrFail($model->rev_id);
        elseif ($model->ticket_id != '')
            $ticket = Ticket::findOrFail($model->ticket_id);

        if ($request->has('top_up_hours')) {
            $shift = Shifts::where(function ($query) use ($model) {
                $query->where('from', '<=', $model->shift_end);
                $query->where('to', '>=', $model->shift_end);
            });
            if (!$shift->count()) {
                toastr()->warning('we can`t find the next shift');
                return back();
            }

            $method = [];
            $method['visit_date'] = date('Y-m-d');
            $method['hours_count'] = $data['top_up_hours'];
            $method['shift_id'] = $shift->first()->id;
            $response = Http::get(route('visitorTypesPrices'), $method);
            $top_up_hours = $request->top_up_hours - $response['latestHours'];
            $data['top_up_hours'] = $top_up_hours + $model->top_up_hours;
            $price = $response['array'][$model->visitor_type_id];
            $data['top_up_price'] = $price + $model->top_up_price;
            $model->shift_end = Carbon::parse($model->shift_end)->addHours($data['top_up_hours']);
            $model->save();


            $ticket->total_top_up_hours += $top_up_hours;
            $ticket->total_top_up_price += $price;
            $ticket->grand_total += $price;
            $ticket->save();

            toastr()->success('top up stored successfully');
        }

        TicketRevModel::findOrFail($id)->update($data);

        if ($model->rev_id != '')
            $printUrl = route('reservations.show',$ticket->id);
        elseif ($model->ticket_id != '')
            $printUrl = route('ticket.edit',$ticket->id);


        return response()->json(['status' => 200,'url'=>$printUrl]);

    }
    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function all($search){
        $ticket = Ticket::where('ticket_num', $search)
            ->orWhereHas('client', function ($query) use ($search) {
                $query->where('phone', $search);
            })
            ->orWhereHas('in_models', function ($query) use ($search) {
                $query->where('bracelet_number', $search);
            })
            ->with('in_models.type')
            ->where('visit_date', date('Y-m-d'));

        if ($ticket->count() == 0) {
            $ticket = Reservations::whereHas('in_models')
                ->where('custom_id', $search)
                ->orWhereHas('in_models', function ($query) use ($search) {
                    $query->where('bracelet_number', $search);
                })
                ->orWhere('phone', $search)
                ->with('in_models.type')
                ->where('day', date('Y-m-d'));
        }
        if ($ticket->count() == 0) {
            toastr()->info('not found');
            return back();
        }
        foreach($ticket->first()->in_models as $model){
            $data['status'] = 'out';
            $model->update($data);

            if ($model->rev_id != '') {
                $ticket = Reservations::findOrFail($model->rev_id);
                $models = TicketRevModel::where('rev_id', $ticket->id)->where('status', 'in');
            }
            elseif($model->ticket_id != ''){
                $ticket = Ticket::findOrFail($model->ticket_id);
                $models = TicketRevModel::where('ticket_id', $ticket->id)->where('status', 'in');

            }else{
                toastr()->info('not found');
                return back();
            }


            if (!$models->count()) {
                $ticket->update($data);
            }
        }
        toastr()->success('Group exit successfully');
        return redirect('capacity?month=' . date('Y-m'));

    }

    public function showTopDown($id)
    {
        $ticket_rev_model = TicketRevModel::findOrFail($id);

        $hours = (int)$ticket_rev_model->shift_end - (int)date('H');
        $diff  = (int)$ticket_rev_model->shift_end - (int)$ticket_rev_model->shift_start;

//        return $hours.'--'.$diff;

//        if($hours != 0){
            return view('sales.layouts.exit.topdown', compact('id','hours','diff'));
//        }

    }

    public function topDown(Request $request, $id)
    {
        $data = $request->validate([
            'top_down_hours' => 'nullable|numeric'
        ]);
        $model = TicketRevModel::findOrFail($id);

        if ($model->rev_id != '')
            $ticket = Reservations::findOrFail($model->rev_id);
        elseif ($model->ticket_id != '')
            $ticket = Ticket::findOrFail($model->ticket_id);

        $models_count = $ticket->models->where('visitor_type_id',$model->visitor_type_id)->count();
        $price_of_one = $model->price / $ticket->hours_count;

        $total_of_down_price = $data['top_down_hours'] * $price_of_one;
        $ticket->total_top_down_price += $total_of_down_price;

//        $ticket->total_top_up_price -= $total_of_down_price;
//        if($ticket->total_top_up_price < 0)
//            $ticket->total_top_up_price = 0;
        $ticket->grand_total        -= $total_of_down_price;
//        $ticket->grand_total_hours -= $data['top_down_hours'];
        $ticket->save();

        $model->shift_end    = Carbon::parse($model->shift_end)->subHours($data['top_down_hours']);
        $model->top_up_hours -= $data['top_down_hours'];
        if($model->top_up_hours < 0)
            $model->top_up_hours = 0;

        // check if cancel
        if($model->shift_start ==  $model->shift_end){
            $model->status = 'out';
            $model->bracelet_number = null;
        }
        $model->save();

        toastr()->success('top Down Done successfully');

        return response()->json(['status' => 200]);

    }

}

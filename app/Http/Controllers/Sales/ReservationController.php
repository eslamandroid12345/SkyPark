<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\CapacityDays;
use App\Models\Category;
use App\Models\DiscountReason;
use App\Models\Event;
use App\Models\GeneralSetting;
use App\Models\Governorate;
use App\Models\Product;
use App\Models\Reservations;
use App\Models\ReservationsBirthDayInfo;
use App\Models\ShiftDetails;
use App\Models\Shifts;
use App\Models\Ticket;
use App\Models\TicketRevModel;
use App\Models\TicketRevProducts;
use App\Models\User;
use App\Models\VisitorTypes;
use Carbon\Carbon;
use Carbon\Traits\Date;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{


    function __construct()
    {

        $this->middleware('permission:Reservation');

    }

    public function index()
    {
        $events = Event::latest()->get();
        $shifts     = Shifts::all();
        return view('sales.reservations',compact('events','shifts'));
    }

    public function searchForReservations(request $request){
        $reservation = Reservations::query();
        $reservation = $reservation->whereHas('models')->where('is_coupon','0');
        if ($request->searchText != null){
            $reservation->where('ticket_num',$request->searchText)
                ->orWhere('phone',$request->searchText)
                ->orWhere('client_name','like','%'.$request->searchText.'%');
        }

        if ($request->has('choices_type') && $request->choices_type != 'all')
            $reservation->where('event_id',$request->choices_type);

        if ($request->has('choices_shift') && $request->choices_shift != 'all')
            $reservation->where('shift_id',$request->choices_shift);

        $reservation = $reservation->whereDate('day', Carbon::today())->get();
        $html = [];
        foreach ($reservation as $rev) {
            $smallArray =[];
            $smallArray[] = '<span data-id="'.$rev->id.'" class="text-success editSpan" style="cursor:pointer">#'.$rev->ticket_num.'</span>';
            $smallArray[] = $rev->day;
            $smallArray[] = $rev->event->title;
            $smallArray[] = $rev->client_name;
            $smallArray[] = $rev->phone;
            $smallArray[] = Carbon::parse($rev->models[0]->shift_start)->format('h:s a');
            $smallArray[] = Carbon::parse($rev->models[0]->shift_end)->format('h:s a');
//            $smallArray[] = date('h a', strtotime($rev->shift->from)).":".date('h a', strtotime($rev->shift->to));
            $smallArray[] = $rev->models->count();
            $accessUrl    = route('groupAccess.index').'?search='.$rev->ticket_num;
            $editUrl      = route('updateReservation',$rev->id);
            $printUrl     = route('reservations.show',$rev->id);
            $title        = $rev->client_name." - ".$rev->ticket_num;
            $editSpan     = null;
            $deleteSpan   = null;
            $accessSpan   = null;
            if($rev->status == 'append')
                $editSpan = '<span class="icon" data-bs-toggle="tooltip" title="edit" data-id="'.$rev->id.'"><a href="'.$editUrl.'"><i class="far fa-edit"></i></a></span>';

            if($rev->status != 'in' && $rev->status != 'out')
                $deleteSpan = '<span class="icon deleteSpan" data-bs-toggle="tooltip" title=" delete " data-title="'.$title.'" data-id="'.$rev->id.'"> <i class="far fa-trash-alt"></i></span>';

            if($rev->status == 'append')
                $accessSpan = '<span class="icon" data-bs-toggle="tooltip" title="Access"><a href="'.$accessUrl.'"><i class="fal fa-check "></i></a></span>';

            $smallArray[] = '<span class="controlIcons">
                  <span class="icon" data-bs-toggle="tooltip" title="print"><a target="_blank" href="'.$printUrl.'"><i class="far fa-print"></i> </a> </span>
                  '.$editSpan.'
                  '.$deleteSpan.'
                  <span class="icon showSpan" data-bs-toggle="tooltip" title=" details " data-id="'.$rev->id.'"> <i class="fas fa-eye"></i></i>  </span>
                  '.$accessSpan.'
                </span>';
            $html[] = $smallArray;
        }
        return response()->json(['html' => $html,'status' => 200]);
    }

    public function editReservation($id){
        $rev   = Reservations::where('id',$id)->first();
        $types = Event::all();
        return view('sales.layouts.reservations.edit',compact('rev','types'));
    }
    public function detailsReservation($id){
        $rev    = Reservations::where('id',$id)->first();
        $products = TicketRevProducts::where('rev_id',$rev->id)->get();
        $models = $rev->models->groupBy('visitor_type_id');
        return view('sales.layouts.reservations.details',compact('rev','models','products'));
    }

    public function update_reservation(request $request){
        $date = $request->validate([
            'day' => 'required|date_format:Y-m-d|after:yesterday',
            'client_name' => 'required|string|max:500',
            'email' => 'nullable|string|max:500',
            'phone' => 'nullable|string|numeric',
            'event_id' => 'required|exists:events,id',
        ]);
        $rev = Reservations::where('id',$request->id)->first();
        if($rev->update($date))
            return response()->json(['status'=>200]);
        else
            return response()->json(['status'=>405]);
    }

    public function delete_reservation(request $request){
        $reservation = Reservations::where('id', $request->id)->first();
        if($reservation->status == 'append') {
            foreach ($reservation->models as $model){
                $model->delete();
            }
            $reservation->delete();
            return response(['message' => 'Data Deleted Successfully', 'status' => 200], 200);
        }
        else
            return response(['message' => "You Can't Delete This Reservation !", 'status' => 405], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $events = Event::latest()->get();
        $governorates = Governorate::with('cities')->latest()->get();
        try {
            $date = date('Y-m-d', base64_decode($request->day));
        } catch (Exception $e) {
            toastr()->warning('You must choose a correct date');
            return redirect('capacity?month=' . date('Y-m'));
        }

        if (date('Y-m-d') > $date) {
            toastr()->warning('You must choose a correct date');
            return redirect('capacity?month=' . date('Y-m'));
        }
        return view('sales.add-reservation', compact('events', 'governorates', 'date'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $date = $request->validate([
            'day' => 'required|date_format:Y-m-d|after:yesterday',
            'client_name' => 'required|string|max:500',
            'email' => 'nullable|string|max:500',
            'phone' => 'nullable|string|numeric',
            'event_id' => 'required|exists:events,id',
            'gov_id' => 'required|exists:governorates,id',
            'city_id' => ['required', Rule::exists('cities', 'id')->where('gov_id', $request->gov_id)],
            'gender' => 'required|in:male,female',

        ]);

        if ($request->event_id == 1) {
            $request->validate([
                'name' => 'required|string|max:500',
                'email_' => 'nullable|string|max:200',
                'gender_' => 'required|in:male,female',
                'phone_' => 'nullable|string|numeric',

            ]);

        }

        $storeMain = Reservations::create($date);
        if ($request->event_id == 1) {
            $revInfo = [];
            $revInfo['name'] = $request->name;
            $revInfo['email'] = $request->email_;
            $revInfo['phone'] = $request->phone;
            $revInfo['gender'] = $request->gender;
            $revInfo['birthday'] = $request->day;
            $revInfo['rev_id'] = $storeMain->id;
            ReservationsBirthDayInfo::create();
        }

        if ($storeMain){
            $url = route('reservations.edit',base64_encode($storeMain->id * 555));
        }
        return response()->json(['status'=>200,'url'=>$url]);

    }

    public function storeRevTicket(request $request){
        if($request->rem == 0)
            $status = '1';
        else
            $status = '0';
        Reservations::where('id',$request->rev_id)->first()->update([
            'add_by'         => auth()->user()->id,
            'shift_id'       => $request->shift_id,
            'hours_count'    => $request->duration,
            'ticket_price'   => $request->ticket_price,
            'ent_tax'        => $request->ent_tax,
            'vat'            => $request->vat,
            'total_price'    => $request->total_price,
            'discount_type'  => $request->discount_type[0],
            'discount_value' => $request->discount_value,
            'ticket_num'     => $request->rand_ticket,
            'custom_id'     => $request->rand_ticket,
            'paid_amount'    => $request->amount,
            'payment_status' => $status,
            'grand_total'    => $request->revenue,
            'rem_amount'     => $request->rem,
            'note'           => $request->note,

        ]);
        for ($i = 0 ; $i < count($request->visitor_type); $i++) {
            TicketRevModel::create([
                'rev_id'          => Reservations::where('id',$request->rev_id)->first()->id,
                'shift_start' => $request->shift_start.':00'.':00',
                'shift_end' => $request->shift_end.':00'.':00',
                'visitor_type_id' => $request->visitor_type[$i],
                'day'             => $request->visit_date,
                'price'           => $request->visitor_price[$i],
                'name'            => $request->visitor_name[$i],
                'birthday'        => $request->visitor_birthday[$i],
                'gender'          => $request->gender[$i],
            ]);
        }
        if($request->has('product_id')) {
            for ($i = 0; $i < count($request->product_id); $i++) {
                TicketRevProducts::create([
                    'rev_id'      => Reservations::where('id',$request->rev_id)->first()->id,
                    'product_id'  => $request->product_id[$i],
                    'category_id' => Product::where('id', $request->product_id[$i])->first()->category_id,
                    'qty'         => $request->product_qty[$i],
                    'price'       => $request->product_price[$i] / $request->product_qty[$i],
                    'total_price' => $request->product_price[$i],
                ]);
            }
        }
        $day = Carbon::parse(Reservations::where('id',$request->rev_id)->first()->day)->format('Y-m');
        $accessUrl    = route('groupAccess.index').'?search='.Reservations::where('id',$request->rev_id)->first()->ticket_num;
        $printUrl     = route('reservations.show',Reservations::where('id',$request->rev_id)->first()->id);
        return response()->json([
            'status' => true,
            'accessUrl' => $accessUrl,
            'printUrl'  => $printUrl,
        ]);
    }


    public function show($id)
    {
        $ticket = Reservations::with('products.product','shift')->findOrFail($id);
        $models = TicketRevModel::where('rev_id',$id)
            ->selectRaw('price, sum(price) as sum_all')
            ->selectRaw('visitor_type_id, count(*) as count_all')
            ->groupby('visitor_type_id')
            ->with('type')
            ->get();
        $date = Carbon::now();
        $product_ids = $ticket->products->pluck('product_id');
        $product_qty = $ticket->products->pluck('qty');
        for ($i = 0 ; $i < count($product_ids) ; $i++){
            // get total prices of all products before tax
            $prices_before_tax[] =  Product::where('id',$product_ids[$i])->first()->price_before_vat * $product_qty[$i];

            // for each vat -> get sum of difference between price before tax and final price
            $product = Product::where([['id',$product_ids[$i]],['vat','!=','0']])->first();
            if($product != null){
                $titles[] = 'vat '.$product->vat.'%';
                $vat[]    = round($product->price * $product_qty[$i] - $prices_before_tax[$i],2);
            }
        }

        // adjust the values
        if (isset($titles) > 0 && isset($vat) > 0){
            foreach ($titles as $key => $value){
                if(isset($titles[$key + 1]) && $value == $titles[$key + 1]){
                    $vat[$key] =  $vat[$key] + $vat[$key + 1];
                    unset($titles[$key + 1]);
                    unset($vat[$key + 1]);
                }
            }
            $titles = array_values($titles);
            $vat    = array_values($vat);
            $sum    = array_sum($prices_before_tax);
            return view('layouts.print.rev', compact('ticket','titles','vat','sum', 'models','date'));
        }

        return view('layouts.print.rev',compact('ticket','models','date'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Application|Factory|\Illuminate\Http\Response|View
     */
    public function edit($id)
    {
        $id = base64_decode($id)/555;
        $ticket = Reservations::findOrFail($id);
        $first_shift_start = Carbon::parse(Shifts::orderBy('from', 'ASC')->first()->from)->format('H');
        $types        = VisitorTypes::all();
        $reservation  = Reservations::findOrFail($id);
        $customId     = strtoupper(date('D').$id.'Re'.substr(time(), -2));
        $shifts       = Shifts::latest()->get();
        $visitorTypes = VisitorTypes::latest()->get();
        $discounts = DiscountReason::all();
        $categories = Category::with(['products'=>function($query){
            $query->where('status','1');
        }])
            ->whereHas('products',function ($q){
                $q->where('status','1');
            })
            ->get();
        $random = substr(Carbon::now()->format("l"),0,3).rand(0, 999).Carbon::now()->format('is');
        return view('sales.reservation-info',compact('id','ticket','discounts','first_shift_start','random','categories','types','reservation','customId','shifts','visitorTypes'));
    }


    public function update($id)
    {
        $rev    = Reservations::where([['id',$id],['status','append']])->firstOrFail();
        $add_by = User::where('id',$rev->add_by)->first()->name;
        $events = Event::all();
        $first_shift_start = Carbon::parse(Shifts::orderBy('from', 'ASC')->first()->from)->format('H');
        $types  = VisitorTypes::all();
        $random = substr(Carbon::now()->format("l"),0,3).rand(0, 999).Carbon::now()->format('is');
        $models = TicketRevModel::where('rev_id',$id)->get();
        $categories = Category::with(['products'=>function($query){
            $query->where('status','1');
        }])->whereHas('products',function ($q){
                $q->where('status','1');
        })->get();
        $discounts = DiscountReason::all();
        $products = TicketRevProducts::where('rev_id',$id)->get();
        $prices = VisitorTypes::where($rev->hours_count.'_hours','!=',0)
            ->select('id as visitor_type_id',$rev->hours_count.'_hours as price')
            ->get();

        return view('sales.updateReservation',compact('rev','add_by','discounts','prices','products','events','models','first_shift_start','types','random','categories'));
    }

    public function calcCapacity($hours_count,$shift_id)
    {
            $shift = Shifts::where('id', $shift_id)->first();
            $hours = $hours_count;
            $shift_duration = strtotime($shift->to) - (strtotime($shift->from));
            $shift_prices = ShiftDetails::where('shift_id', $shift_id)->select('visitor_type_id', 'price')->get();
            // now check if wanted hours is less than shift time then do direct calculations
            if ($hours <= $shift_duration / 3600) {
                foreach ($shift_prices as $price) {
                    $price->price *= $hours;
                }
                return $shift_prices;
            } else {
                // do function
                $visitorTypes = VisitorTypes::latest()->get();
                $hoursCount = $hours_count;
                $newHoursCount = $hours_count;
                $minHorse = 0;

                $shift = Shifts::findOrFail($shift_id);
                $shifts = [];
                $prices = [];
                $pricesArray = [];
                $searchHourArray = [];
                foreach ($visitorTypes as $visitorType) {
                    $pricesArray[$visitorType->id] = 0;
                }

                while ($newHoursCount > 0) {

                    $from = strtotime(date('H', strtotime($shift->from)) . ":00");
                    $to = strtotime(date('H', strtotime($shift->to)) . ":00");
                    $difference = round(abs($to - $from) / 3600, 2);
                    if ($hoursCount > $difference) {
                        $searchHour = $difference;
                    } else {
                        $searchHour = $hoursCount;
                    }

                    if ($newHoursCount < $difference) {
                        $searchHour = $newHoursCount;
                    }


                    foreach ($visitorTypes as $visitorType) {
                        $findShiftDetails = ShiftDetails::where('shift_id', $shift->id)->where('visitor_type_id', $visitorType->id)->firstOrFail();
                        $shifts[] = $findShiftDetails;
                        $pricesArray[$visitorType->id] += $searchHour * $findShiftDetails->price;
                    }
                    $nextId = Shifts::whereTime('from', '>=', $shift->to)->max('id');
                    $latestShift = $shift;
                    $shift = Shifts::find($nextId);
                    $newHoursCount = $newHoursCount - $searchHour;

                    if (!$shift) {
                        break;
                    }
                }
                $shift_prices = [];
                foreach ($pricesArray as $key => $item) {
                    $smallArray = [];
                    $smallArray['visitor_type_id'] = $key;
                    $smallArray['price'] = $item;
                    $shift_prices[] = $smallArray;
                }
                return $shift_prices;
            }
        }


    public function postUpdateReservation(request $request){
        $rev = Reservations::where('id',$request->rev_id)->first();
        $day = $rev->models->first()->day;
        foreach ($rev->models as $model){
            $model->delete();
        }
        if($request->rem == 0)
            $status = '1';
        else
            $status = '0';
        $rev->update([
            'client_name'    => $request->client_name,
            'phone'          => $request->phone,
            'email'          => $request->email,
            'total_price'    => $request->total_price,
            'discount_type'  => $request->discount_type[0],
            'discount_value' => $request->discount_value,
            'paid_amount'    => $request->amount,
            'grand_total'    => $request->revenue,
            'ticket_price'   => $request->ticket_price,
            'ent_tax'        => $request->ent_tax,
            'vat'            => $request->vat,
            'rem_amount'     => $request->rem,
            'payment_status' => $status,
            'note'           => $request->note,

        ]);
        for ($i = 0 ; $i < count($request->visitor_type); $i++) {
            TicketRevModel::create([
                'rev_id'          => $rev->id,
                'day'             => $day,
                'shift_start'     => $request->shift_start,
                'shift_end'       => $request->shift_end,
                'visitor_type_id' => $request->visitor_type[$i],
                'price'           => $request->visitor_price[$i],
                'name'            => $request->visitor_name[$i],
                'birthday'        => $request->visitor_birthday[$i],
                'gender'          => ($request->gender[$i]) ?? null,
            ]);
        }
        foreach ($rev->products as $product){
            $product->delete();
        }
        if($request->has('product_id')) {
            for ($i = 0; $i < count($request->product_id); $i++) {
                TicketRevProducts::create([
                    'rev_id'      => $rev->id,
                    'product_id'  => $request->product_id[$i],
                    'category_id' => Product::where('id', $request->product_id[$i])->first()->category_id,
                    'qty'         => $request->product_qty[$i],
                    'price'       => $request->product_price[$i] / $request->product_qty[$i],
                    'total_price' => $request->product_price[$i],
                ]);
            }
        }
        $day = Carbon::parse(Reservations::where('id',$request->rev_id)->first()->day)->format('Y-m');
        $accessUrl    = route('groupAccess.index').'?search='.$rev->ticket_num;
        $printUrl     = route('reservations.show',$rev->id);
        return response()->json([
            'status' => true,
            'accessUrl' => $accessUrl,
            'printUrl'  => $printUrl,
        ]);
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
}

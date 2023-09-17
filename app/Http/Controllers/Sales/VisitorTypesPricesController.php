<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\CapacityDays;
use App\Models\ShiftDetails;
use App\Models\Shifts;
use App\Models\TopUpPrice;
use App\Models\VisitorTypes;
use Illuminate\Http\Request;

class VisitorTypesPricesController extends Controller{


    public function visitorTypesPrices(Request $request){
        $date = $request->validate([
            'visit_date'    =>'required',
            'hours_count'   =>'required',
//            'shift_id'      =>'required|exists:shifts,id|exists:shift_details,shift_id',
            ]);


        $hoursCount    = $request->hours_count;
        $newHoursCount = 0;
        $visitorTypes  = VisitorTypes::latest()->get()->pluck('id');
        foreach ($visitorTypes as $type){
            $price = TopUpPrice::where('type_id',$type)->first();
            if($price)
                $pricesArray[$type] = ($price[$hoursCount.'_hours'] * $hoursCount);
            else
                $pricesArray[$type] = (65 * $hoursCount);
        }
//
//        $hoursCount    = $request->hours_count;
//        $newHoursCount = $request->hours_count;
//        $shift  = Shifts::findOrFail($request->shift_id);
//        $shifts = [];
////        $pricesArray = [];
////        foreach($visitorTypes as $visitorType){
////            $pricesArray[$visitorType->id] = 0;
////        }
//
//        $count = 0;
//
//
//
//        while ($newHoursCount > 0){
//
//            if ($count >= 100)
//            {
//                break;
//            }
//
//
//            $from = strtotime(date('H',strtotime($shift->from)).":00");
//            $to = strtotime(date('H',strtotime($shift->to)).":00");
//            $difference = round(abs($to - $from) / 3600,2);
//            if ($hoursCount > $difference){
//                $searchHour = $difference;
//            }else{
//                $searchHour = $hoursCount;
//            }
//
//            if ($newHoursCount < $difference){
//                $searchHour = $newHoursCount;
//            }
//
//
//            foreach($visitorTypes as $visitorType){
//                $findShiftDetails = ShiftDetails::where('shift_id',$shift->id)->where('visitor_type_id',$visitorType->id)->firstOrFail();
//                $shifts[] = $findShiftDetails;
////                $pricesArray[$visitorType->id] += $searchHour * $findShiftDetails->price;
//            }
//            $nextId = Shifts::where('id','>',$shift->id)->max('id');
//            $latestShift = $shift;
//
//            $shift = Shifts::find($nextId);
//
//
//
//            $newHoursCount = $newHoursCount - $searchHour;
//
//            if (!$shift){
//                break;
//            }
//            $count++;
//        }
        return response()->json(['status'=>200,'array'=>$pricesArray,'latestHours'=>$newHoursCount]);





    }//end fun

    public function calculateWithVisitorType(){

    }//end fun


    public function calcCapacity(Request $request){
        $request->validate([
            'visit_date'    =>'required',
            'hours_count'   =>'required',
            'shift_id'      =>'required',
        ]);
        $capacity     = (CapacityDays::where('day',$request->visit_date)->first()->count) ?? GeneralSetting::first()->capacity;
        $booked_count = TicketRevModel::where('day',$request->visit_date)->count();

        // if booked = or > from the day wanted then the day is full
        if($booked_count >= $capacity)
            return response()->json(['day'=>$request->visit_date,'status' => false]);
        else{
            // else then there is a count for new tickets and adjust response
            $available      = $capacity - $booked_count;
            $shift          = Shifts::where('id',$request->shift_id)->first();
            $hours          = $request->hours_count;
            $shift_duration = strtotime($shift->to)-(strtotime($shift->from));
            $shift_prices   = ShiftDetails::where('shift_id',$request->shift_id)->select('visitor_type_id','price')->get();
            // now check if wanted hours is less than shift time then do direct calculations
            if($hours <= $shift_duration/3600) {
                foreach ($shift_prices as $price){
                    $price->price *= $hours;
                }
                return response()->json(['shift_prices' => $shift_prices,'available' => $available,'status' => true]);
            }else{
                // do function
            }
        }
    }


}//end class

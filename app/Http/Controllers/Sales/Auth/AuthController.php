<?php

namespace App\Http\Controllers\Sales\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bracelets;
use App\Models\DiscountReason;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TopUpPrice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ifsnop\Mysqldump as IMysqldump;
use App\Classes\Import;
class AuthController extends Controller
{
    public function __construct()
    {
        ini_set("max_execution_time",300);
        $this->middleware('auth')->only('logout');
    }

    public function view()
    {
        if (auth()->check()) {
            return redirect('/sales');
        }
        return view('sales.auth.login');
    }//end fun

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $data = $request->validate([
            'user_name' => 'required|exists:users',
            'password' => 'required'
        ]);


        if (auth()->attempt($data)) {
            return response()->json(200);
        }
        return response()->json(405);
    }//end fun

    public function logout()
    {
        auth()->logout();
        toastr()->info('logged out successfully');
        return redirect('login');
    }//end fun


    public function uploadData(Request $request)
    {
        if (!$this->is_connected()) {
            return false;
        }


        $clients = \App\Models\Clients::where('uploaded', false)->get();
        $tickets = \App\Models\Ticket::where('uploaded', false)->with('models', 'products')->get();
        $reservations = \App\Models\Reservations::where('uploaded', false)->with('models', 'products')->get();
        $discount_reasons = \App\Models\DiscountReason::where('uploaded', false)->get();
//        $bracelets = \App\Models\Bracelets::where('uploaded', false)->get();
        $products   = \App\Models\Product::where('uploaded', false)->get();
        $payments   = \App\Models\Payment::where('uploaded', false)->get();
        $top_prices = \App\Models\TopUpPrice::where('uploaded', false)->get();


        \App\Models\Clients::where('uploaded', false)->update(['uploaded' => true]);
        \App\Models\Ticket::where('uploaded', false)->update(['uploaded' => true]);
        \App\Models\Reservations::where('uploaded', false)->update(['uploaded' => true]);
        DiscountReason::where('uploaded', false)->update(['uploaded' => true]);
        TopUpPrice::where('uploaded', false)->update(['uploaded' => true]);
//        Bracelets::where('uploaded', false)->update(['uploaded' => true]);
        Product::where('uploaded', false)->update(['uploaded' => true]);
        Payment::where('uploaded', false)->update(['uploaded' => true]);
        DB::purge('offline');
        DB::setDefaultConnection('online');

        foreach ($clients as $client) {
            $storeClientData = [];
            $storeClientData['name'] = $client->name;
            $storeClientData['phone'] = $client->phone;
            $storeClientData['email'] = $client->email;
            $storeClientData['gender'] = $client->gender;
            $storeClientData['rate'] = $client->rate;
            $storeClientData['note'] = $client->note;
            $storeClientData['gov_id'] = $client->gov_id;
            $storeClientData['city_id'] = $client->city_id;
            $storeClientData['ref_id'] = $client->ref_id;
            $storeClientData['family_size'] = $client->family_size;
            $storeClientData['uploaded'] = 1;

            \App\Models\Clients::updateOrCreate($storeClientData);
        }
        foreach ($tickets as $ticket) {
            $storeTicketData = [];
            $storeTicketData['add_by'] = $ticket->add_by;
            $storeTicketData['ticket_num'] = $ticket->ticket_num;
            $storeTicketData['visit_date'] = $ticket->visit_date;
            $storeTicketData['shift_id'] = $ticket->shift_id;
            $storeTicketData['hours_count'] = $ticket->hours_count;
            $storeTicketData['total_price'] = $ticket->total_price;
            $storeTicketData['total_top_up_hours'] = $ticket->total_top_up_hours;
            $storeTicketData['total_top_up_price'] = $ticket->total_top_up_price;
            $storeTicketData['total_top_down_price'] = $ticket->total_top_down_price;
            $storeTicketData['payment_method'] = $ticket->payment_method;
            $storeTicketData['payment_status'] = $ticket->payment_status;
            $storeTicketData['note'] = $ticket->note;
            $storeTicketData['discount_type'] = $ticket->discount_type;
            $storeTicketData['discount_value'] = $ticket->discount_value;
            $storeTicketData['discount_id'] = $ticket->discount_id;
            $storeTicketData['ticket_price'] = $ticket->ticket_price;
            $storeTicketData['ent_tax'] = $ticket->ent_tax;
            $storeTicketData['vat'] = $ticket->vat;
            $storeTicketData['grand_total'] = $ticket->grand_total;
            $storeTicketData['paid_amount'] = $ticket->paid_amount;
            $storeTicketData['rem_amount'] = $ticket->rem_amount;
            $storeTicketData['status'] = $ticket->status;
            $storeTicketData['uploaded'] = 1;

            $storeTicket = \App\Models\Ticket::create($storeTicketData);

            foreach ($ticket->models as $model) {
                $smallModelTicket = [];
                $smallModelTicket['visitor_type_id'] = $model->visitor_type_id;
                $smallModelTicket['coupon_num'] = $model->coupon_num;
                $smallModelTicket['day'] = $model->day;
                $smallModelTicket['price'] = $model->price;
                $smallModelTicket['bracelet_id'] = $model->bracelet_id;
                $smallModelTicket['bracelet_number'] = $model->bracelet_number;
                $smallModelTicket['name'] = $model->name;
                $smallModelTicket['birthday'] = $model->birthday;
                $smallModelTicket['gender'] = $model->gender;
                $smallModelTicket['status'] = $model->status;
                $smallModelTicket['top_up_hours'] = $model->top_up_hours;
                $smallModelTicket['top_up_price'] = $model->top_up_price;
                $smallModelTicket['start_at'] = $model->start_at;
                $smallModelTicket['end_at'] = $model->end_at;
                $smallModelTicket['shift_start'] = $model->shift_start;
                $smallModelTicket['shift_end'] = $model->shift_end;
                $smallModelTicket['temp_status'] = $model->temp_status;
                $storeTicket->models()->create($smallModelTicket);
            }
            foreach ($ticket->products as $product) {
                $smallProductTicket = [];
                $smallProductTicket['category_id'] = $product->category_id;
                $smallProductTicket['product_id'] = $product->product_id;
                $smallProductTicket['qty'] = $product->qty;
                $smallProductTicket['price'] = $product->price;
                $smallProductTicket['total_price'] = $product->total_price;
                $storeTicket->products()->create($smallProductTicket);
            }

        }

        foreach ($reservations as $reservation) {
            $storeReservationData = [];
            $storeReservationData['add_by'] = $reservation->add_by;
            $storeReservationData['ticket_num'] = $reservation->ticket_num;
            $storeReservationData['custom_id'] = $reservation->custom_id;
            $storeReservationData['day'] = $reservation->day;
            $storeReservationData['client_name'] = $reservation->client_name;
            $storeReservationData['phone'] = $reservation->phone;
            $storeReservationData['email'] = $reservation->email;
            $storeReservationData['gender'] = $reservation->gender;
            $storeReservationData['gov_id'] = $reservation->gov_id;
            $storeReservationData['city_id'] = $reservation->city_id;
            $storeReservationData['event_id'] = $reservation->event_id;
            $storeReservationData['shift_id'] = $reservation->shift_id;
            $storeReservationData['hours_count'] = $reservation->hours_count;
            $storeReservationData['total_price'] = $reservation->total_price;
            $storeReservationData['total_top_up_hours'] = $reservation->total_top_up_hours;
            $storeReservationData['total_top_up_price'] = $reservation->total_top_up_price;
            $storeReservationData['total_top_down_price'] = $reservation->total_top_down_price;
            $storeReservationData['payment_method'] = $reservation->payment_method;
            $storeReservationData['payment_status'] = $reservation->payment_status;
            $storeReservationData['note'] = $reservation->note;
            $storeReservationData['discount_type'] = $reservation->discount_type;
            $storeReservationData['discount_value'] = $reservation->discount_value;
            $storeReservationData['discount_id'] = $reservation->discount_id;
            $storeReservationData['ticket_price'] = $reservation->ticket_price;
            $storeReservationData['ent_tax'] = $reservation->ent_tax;
            $storeReservationData['vat'] = $reservation->vat;
            $storeReservationData['grand_total'] = $reservation->grand_total;
            $storeReservationData['paid_amount'] = $reservation->paid_amount;
            $storeReservationData['rem_amount'] = $reservation->rem_amount;
            $storeReservationData['status'] = $reservation->status;
            $storeReservationData['is_coupon'] = $reservation->is_coupon;
            $storeReservationData['coupon_start'] = $reservation->coupon_start;
            $storeReservationData['coupon_end'] = $reservation->coupon_end;
            $storeReservationData['uploaded'] = 1;

            $storeReservation = \App\Models\Reservations::create($storeReservationData);

            foreach ($reservation->models as $model) {
                $smallModelReservation = [];
                $smallModelReservation['visitor_type_id'] = $model->visitor_type_id;
                $smallModelReservation['coupon_num'] = $model->coupon_num;
                $smallModelReservation['day'] = $model->day;
                $smallModelReservation['price'] = $model->price;
                $smallModelReservation['bracelet_id'] = $model->bracelet_id;
                $smallModelReservation['bracelet_number'] = $model->bracelet_number;
                $smallModelReservation['name'] = $model->name;
                $smallModelReservation['birthday'] = $model->birthday;
                $smallModelReservation['gender'] = $model->gender;
                $smallModelReservation['status'] = $model->status;
                $smallModelReservation['top_up_hours'] = $model->top_up_hours;
                $smallModelReservation['top_up_price'] = $model->top_up_price;
                $smallModelReservation['start_at'] = $model->start_at;
                $smallModelReservation['end_at'] = $model->end_at;
                $smallModelReservation['shift_start'] = $model->shift_start;
                $smallModelReservation['shift_end'] = $model->shift_end;
                $smallModelReservation['temp_status'] = $model->temp_status;
                $storeReservation->models()->create($smallModelReservation);
            }
            foreach ($reservation->products as $product) {
                $smallProductReservation = [];
                $smallProductReservation['category_id'] = $product->category_id;
                $smallProductReservation['product_id'] = $product->product_id;
                $smallProductReservation['qty'] = $product->qty;
                $smallProductReservation['price'] = $product->price;
                $smallProductReservation['total_price'] = $product->total_price;
                $storeReservation->products()->create($smallProductReservation);
            }

        }

        foreach ($discount_reasons as $reason){
            $storeReasonData = [];
            $storeReasonData['desc']     = $reason->desc;
            $storeReasonData['uploaded'] = 1;
            DiscountReason::updateOrCreate($storeReasonData);
        }
        foreach ($top_prices as $price){
            $storeData = [];
            $storeData['type_id']  = $price->type_id;
            $storeData['1_hours']  = $price['1_hours'];
            $storeData['2_hours']  = $price['2_hours'];
            $storeData['3_hours']  = $price['3_hours'];
            $storeData['4_hours']  = $price['4_hours'];
            $storeData['5_hours']  = $price['5_hours'];
            $storeData['uploaded'] = 1;
            TopUpPrice::updateOrCreate($storeData);
        }

        foreach ($products as $product){
            $storeProductData = [];
            $storeProductData['title']       = $product->title;
            $storeProductData['category_id'] = $product->category_id;
            $storeProductData['status']      = $product->status;
            $storeProductData['vat']              = $product->vat;
            $storeProductData['price_before_vat'] = $product->price_before_vat;
            $storeProductData['price'] = $product->price;
            $storeProductData['uploaded'] = 1;
            Product::updateOrCreate($storeProductData);
        }

        foreach ($payments as $payment){
            $storePaymentData = [];
            $storePaymentData['rev_id']         = $payment->rev_id;
            $storePaymentData['ticket_id']      = $payment->ticket_id;
            $storePaymentData['payment_status'] = $payment->payment_status;
            $storePaymentData['amount']         = $payment->amount;
            $storePaymentData['uploaded']       = 1;
            Payment::updateOrCreate($storePaymentData);
        }


        $dump = new IMysqldump\Mysqldump('mysql:host=45.84.204.1;dbname=u346577485_skypark', 'u346577485_skypark', 'U346577485_skypark');
        if (file_exists('databases/' . date('Y-m-d') . '-onlineSky.sql')) {
            unlink('databases/' . date('Y-m-d') . '-onlineSky.sql');
        }
        $dump->start('databases/' . date('Y-m-d') . '-onlineSky.sql');

        $dbFile = public_path('databases/' . date('Y-m-d') . '-onlineSky.sql');
        $dropMysqli = new \mysqli("localhost", env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'));
        $dropMysqli->query('SET foreign_key_checks = 0');
        if ($result = $dropMysqli->query("SHOW TABLES")) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $dropMysqli->query('DROP TABLE IF EXISTS `' . $row[0] . '`');
            }
        }

        $dropMysqli->query('SET foreign_key_checks = 1');
        $dropMysqli->close();
        new Import($dbFile, env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'), 'localhost');

        DB::purge('online');
        DB::setDefaultConnection('offline');
        return response()->json(['status' => 200]);

    }//end fun

    /**
     * @return bool
     */
    private function is_connected()
    {
        $connected = @fsockopen("www.example.com", 80);
        //website, port  (try 80 or 443)
        if ($connected) {
            $is_conn = true; //action when connected
            fclose($connected);
        } else {
            $is_conn = false; //action in connection failure
        }
        return $is_conn;

    }//end fun
}//end class

<?php
namespace App\Http\Controllers;
use App\Http\Controllers\OTPVerificationController;
use App\Models\BusinessSetting;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Notifications\AppEmailVerificationNotification;
use Hash;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\ProductStock;
use App\Models\Product;
use App\Models\CustomerRoute;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
  public function exportcustomers(Request $request)
    {
     return Excel::download(new CustomersExport, time().'customers.xlsx');
    }
}

?>
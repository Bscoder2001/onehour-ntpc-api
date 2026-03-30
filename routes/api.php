use App\Models\NtpcForm;
use Illuminate\Http\Request;

Route::post('/submit-form', function (Request $request) {

    $data = NtpcForm::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Form submitted successfully',
        'data' => $data
    ]);
});
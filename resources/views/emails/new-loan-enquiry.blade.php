<h1>New website loan enquiry</h1>
<p>Reference: <strong>{{ $enquiry->reference_number }}</strong></p>
<dl>
    <dt>Name</dt><dd>{{ $enquiry->customer_name }}</dd>
    <dt>Mobile</dt><dd>{{ $enquiry->mobile_number }}</dd>
    <dt>Email</dt><dd>{{ $enquiry->email ?? 'Not provided' }}</dd>
    <dt>City</dt><dd>{{ $enquiry->city }}</dd>
    <dt>Loan category</dt><dd>{{ $enquiry->loan_category }}</dd>
    <dt>Requested amount</dt><dd>₹{{ number_format((float) $enquiry->requested_amount, 2) }}</dd>
    <dt>Preferred tenure</dt><dd>{{ $enquiry->preferred_tenure }}</dd>
    <dt>Vehicle condition</dt><dd>{{ $enquiry->vehicle_condition }}</dd>
    <dt>Preferred callback time</dt><dd>{{ $enquiry->preferred_callback_time }}</dd>
</dl>

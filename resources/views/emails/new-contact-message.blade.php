<h1>New website contact message</h1>
<p>Reference: <strong>{{ $contactMessage->reference_number }}</strong></p>
<dl>
    <dt>Name</dt><dd>{{ $contactMessage->name }}</dd>
    <dt>Mobile</dt><dd>{{ $contactMessage->mobile_number }}</dd>
    <dt>Email</dt><dd>{{ $contactMessage->email ?? 'Not provided' }}</dd>
    <dt>Topic</dt><dd>{{ str($contactMessage->subject)->replace('_', ' ')->headline() }}</dd>
    <dt>Message</dt><dd>{{ $contactMessage->message }}</dd>
</dl>

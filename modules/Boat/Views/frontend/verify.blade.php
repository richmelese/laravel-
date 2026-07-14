<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $valid ? __('Valid Ticket') : __('Invalid Ticket') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: {{ $valid ? '#eafaf0' : '#fdecea' }};
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #1f2a37;
            padding: 16px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 24px rgba(16, 24, 40, 0.1);
            padding: 36px 28px;
            max-width: 380px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 36px;
            color: #fff;
            background: {{ $valid ? '#2f9e5c' : '#e0483c' }};
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            color: {{ $valid ? '#1e7e45' : '#c23327' }};
        }
        .sub {
            color: #8592a6;
            font-size: 13px;
            margin-bottom: 22px;
        }
        .details {
            text-align: left;
            border-top: 1px dashed #e2e6ec;
            padding-top: 18px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }
        .row .label { color: #8592a6; }
        .row .value { font-weight: 700; color: #10254c; text-align: right; }
        .badge {
            display: inline-block;
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 3px 10px;
            border-radius: 999px;
            background: {{ $valid ? '#e7f7ee' : '#fdecea' }};
            color: {{ $valid ? '#1e7e45' : '#c23327' }};
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $valid ? '✓' : '✕' }}</div>
        <h1>{{ $valid ? __('Valid Ticket') : __('Invalid Ticket') }}</h1>
        <div class="sub">{{ $ticket['ticket_id'] ?? '' }}</div>

        @if($valid)
            <div class="details">
                <div class="row">
                    <span class="label">{{ __('Passenger') }}</span>
                    <span class="value">{{ $ticket['passenger_name'] ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">{{ __('Seat') }}</span>
                    <span class="value">{{ $ticket['seat_number'] ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">{{ __('Route') }}</span>
                    <span class="value">{{ $ticket['from_location'] ?? '' }} &#8594; {{ $ticket['to_location'] ?? '' }}</span>
                </div>
                <div class="row">
                    <span class="label">{{ __('Departure') }}</span>
                    <span class="value">
                        {{ $ticket['departure_time'] ? \Illuminate\Support\Carbon::parse($ticket['departure_time'])->format('M d, Y - h:i A') : '-' }}
                    </span>
                </div>
                <div class="row">
                    <span class="label">{{ __('Status') }}</span>
                    <span class="value"><span class="badge">{{ $ticket['status'] ?? '' }}</span></span>
                </div>
            </div>
        @else
            <div class="sub">{{ __('This ticket could not be verified — it may not exist, or the booking is not a confirmed, paid ticket.') }}</div>
        @endif
    </div>
</body>
</html>

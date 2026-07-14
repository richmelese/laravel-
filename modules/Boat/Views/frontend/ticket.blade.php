<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Bus Ticket') }} — {{ $ticket['ticket_id'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px 16px;
            background: #eef1f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #1f2a37;
        }
        .wrap {
            max-width: 720px;
            margin: 0 auto;
        }
        .flash {
            background: #e7f7ee;
            border: 1px solid #b7e4c7;
            color: #1e7e45;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 600;
            text-align: center;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(16, 24, 40, 0.08);
            overflow: hidden;
        }
        .brand {
            padding: 18px 28px;
            border-bottom: 1px solid #eef1f5;
            text-align: center;
        }
        .brand .brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #10254c;
        }
        .brand .brand-tagline {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #8592a6;
            margin-top: 2px;
        }
        .ticket {
            display: grid;
            grid-template-columns: 1fr 220px;
        }
        @media (max-width: 560px) {
            .ticket { grid-template-columns: 1fr; }
        }
        .ticket-main {
            padding: 28px;
        }
        .route {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }
        .route .city {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: .3px;
            color: #10254c;
        }
        .route .arrow {
            color: #3b82f6;
            font-size: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 24px;
        }
        .field .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #8592a6;
            margin-bottom: 4px;
        }
        .field .value {
            font-size: 15px;
            font-weight: 700;
            color: #10254c;
        }
        .ticket-side {
            background: #f6f8fb;
            border-left: 1px dashed #d7dce3;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        @media (max-width: 560px) {
            .ticket-side { border-left: none; border-top: 1px dashed #d7dce3; }
        }
        .ticket-side .scan-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #8592a6;
            margin-bottom: 10px;
        }
        .ticket-side img {
            width: 150px;
            height: 150px;
            background: #fff;
            border-radius: 8px;
            padding: 8px;
            border: 1px solid #e5e9f0;
        }
        .ticket-side .ticket-id-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #8592a6;
            margin-top: 14px;
        }
        .ticket-side .ticket-id {
            font-weight: 800;
            font-size: 14px;
            color: #10254c;
            letter-spacing: .5px;
        }
        .seat-badge {
            margin-top: 14px;
            display: inline-block;
            background: #2f6fed;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 18px;
            border-radius: 8px;
        }
        .notice {
            margin-top: 16px;
            background: #fff8e6;
            border: 1px solid #f2dfa6;
            border-radius: 10px;
            padding: 16px 20px;
        }
        .notice h3 {
            margin: 0 0 8px;
            font-size: 14px;
            color: #8a5a00;
        }
        .notice ul {
            margin: 0;
            padding-left: 18px;
            color: #6b5015;
            font-size: 13px;
            line-height: 1.6;
        }
        .actions {
            text-align: center;
            margin-top: 18px;
        }
        .actions button {
            background: #10254c;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            font-size: 14px;
            cursor: pointer;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .actions, .flash { display: none; }
            .card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        @if(!empty($message))
            <div class="flash">{{ $message }}</div>
        @endif

        <div class="card">
            <div class="brand">
                <div class="brand-name">TONETOR</div>
                <div class="brand-tagline">{{ __('Digital Transport Ticketing Network') }}</div>
            </div>
            <div class="ticket">
                <div class="ticket-main">
                    <div class="route">
                        <span class="city">{{ $ticket['from_location'] ?? '' }}</span>
                        <span class="arrow">&#8594;</span>
                        <span class="city">{{ $ticket['to_location'] ?? '' }}</span>
                    </div>

                    <div class="grid">
                        <div class="field">
                            <div class="label">{{ __('Passenger Name') }}</div>
                            <div class="value">{{ $ticket['passenger_name'] }}</div>
                        </div>
                        <div class="field">
                            <div class="label">{{ __('Phone') }}</div>
                            <div class="value">{{ $ticket['phone'] }}</div>
                        </div>

                        <div class="field">
                            <div class="label">{{ __('Departure Date & Time') }}</div>
                            <div class="value">
                                {{ optional($ticket['departure_time'])->format('M d, Y - h:i A') }}
                            </div>
                        </div>
                        <div class="field">
                            <div class="label">{{ __('Bus Plate Number') }}</div>
                            <div class="value">{{ $ticket['bus_number'] ?? '-' }}</div>
                        </div>

                        <div class="field">
                            <div class="label">{{ __('Bus / Tier') }}</div>
                            <div class="value">{{ $ticket['bus_title'] ?? '-' }} @if(!empty($ticket['bus_level']))({{ ucfirst($ticket['bus_level']) }})@endif</div>
                        </div>
                        <div class="field">
                            <div class="label">{{ __('Total Fare Paid') }}</div>
                            <div class="value">{{ number_format($ticket['total_paid'], 2) }} {{ $ticket['currency'] }}</div>
                        </div>
                    </div>

                    <div class="notice">
                        <h3>{{ __('Boarding Guidelines') }}</h3>
                        <ul>
                            <li>{{ __('Please arrive at least 15 minutes before departure.') }}</li>
                            <li>{{ __('Present this ticket (QR code) at boarding for verification.') }}</li>
                            <li>{{ __('This ticket is valid for the passenger and seat shown above only.') }}</li>
                        </ul>
                    </div>
                </div>

                <div class="ticket-side">
                    <div class="scan-label">{{ __('Scan for verification') }}</div>
                    <img src="{{ $ticket['qr_code_url'] }}" alt="QR code">
                    <div class="ticket-id-label">{{ __('Ticket ID') }}</div>
                    <div class="ticket-id">{{ $ticket['ticket_id'] }}</div>
                    <span class="seat-badge">{{ __('Seat') }} {{ $ticket['seat_number'] }}</span>
                </div>
            </div>
        </div>

        <div class="actions">
            <button onclick="window.print()">{{ __('Print Ticket') }}</button>
        </div>
    </div>
</body>
</html>

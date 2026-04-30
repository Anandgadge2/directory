<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page { margin: 15mm 15mm; size: A4; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; line-height: 1.4; background: #fff; }
        
        /* Header Section */
        .report-header { padding-bottom: 20px; border-bottom: 2px solid #1a3a5a; margin-bottom: 20px; }
        .company-info { width: 60%; float: left; }
        .company-name { font-size: 18px; font-weight: bold; color: #1a3a5a; text-transform: uppercase; }
        .company-address { font-size: 9px; color: #666; margin-top: 5px; }
        .report-meta { width: 35%; float: right; text-align: right; }
        .document-title { font-size: 14px; font-weight: bold; color: #c9a961; text-transform: uppercase; margin-bottom: 5px; }
        .meta-text { font-size: 8px; color: #888; }
        .clearfix { clear: both; }

        /* Filter & KPI Section */
        .section-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .filter-row { display: table; width: 100%; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 8px; }
        .filter-item { display: table-cell; width: 25%; font-size: 8px; }
        .filter-label { font-weight: bold; color: #1a3a5a; }
        
        .kpi-row { display: table; width: 100%; margin-top: 10px; }
        .kpi-item { display: table-cell; text-align: center; border-right: 1px solid #e2e8f0; }
        .kpi-item:last-child { border-right: none; }
        .kpi-value { font-size: 16px; font-weight: bold; color: #1a3a5a; display: block; }
        .kpi-label { font-size: 7px; color: #64748b; text-transform: uppercase; font-weight: bold; }

        /* Tables */
        .table-title { font-size: 11px; font-weight: bold; color: #1a3a5a; margin-bottom: 10px; text-transform: uppercase; border-left: 4px solid #c9a961; padding-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        th { background: #1a3a5a; color: #fff; text-align: left; padding: 8px 5px; font-size: 8px; text-transform: uppercase; font-weight: bold; }
        td { padding: 7px 5px; border-bottom: 1px solid #edf2f7; font-size: 8px; vertical-align: top; word-wrap: break-word; }
        tr:nth-child(even) { background: #fdfdfd; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        /* Utility Classes */
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 7px; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-info { background: #e0f2fe; color: #075985; }
        
        /* Footer */
        .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 7px; color: #94a3b8; text-align: center; }
        
        /* Page Break */
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="company-info">
            <div class="company-name">{{ $company['name'] ?? 'AI Patrolling System' }}</div>
            <div class="company-address">
                {{ $company['address'] ?? 'Official Forest Department HQ' }}<br>
                {{ $company['email'] ?? 'ops@fsm-forest.gov' }} | {{ $company['contact'] ?? '+91 800 000 0000' }}
            </div>
        </div>
        <div class="report-meta">
            <div class="document-title">Official Performance Report</div>
            <div class="meta-text">Report ID: FSM-{{ time() }}</div>
            <div class="meta-text">Date Generated: {{ now()->format('d M Y, h:i A') }}</div>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="section-box">
        <div class="filter-row">
            @foreach($filters as $label => $val)
                <div class="filter-item">
                    <span class="filter-label">{{ $label }}:</span><br>
                    {{ $val }}
                </div>
            @endforeach
        </div>
        <div class="kpi-row">
            @foreach($kpis as $label => $value)
                <div class="kpi-item">
                    <span class="kpi-value">{{ $value }}</span>
                    <span class="kpi-label">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Analytical Summary Section --}}
    <div class="table-title">Management Summary & Analytics</div>
    <table>
        <thead>
            @if($type == 'attendance')
                <tr>
                    <th style="width: 10%;">Rank</th>
                    <th style="width: 35%;">Guard Name</th>
                    <th style="width: 15%;" class="text-center">Days Active</th>
                    <th style="width: 15%;" class="text-center">Late Marks</th>
                    <th style="width: 25%;" class="text-right">Efficiency Rate</th>
                </tr>
            @elseif($type == 'patrol' || $type == 'night_patrol')
                <tr>
                    <th style="width: 40%;">Guard Name</th>
                    <th style="width: 15%;" class="text-center">Sessions</th>
                    <th style="width: 15%;" class="text-center">Distance</th>
                    <th style="width: 15%;" class="text-center">Avg Speed</th>
                    <th style="width: 15%;" class="text-center">Total Time</th>
                </tr>
            @elseif($type == 'incident')
                <tr>
                    <th style="width: 15%;">Time</th>
                    <th style="width: 20%;">Guard</th>
                    <th style="width: 20%;">Type</th>
                    <th style="width: 15%;">Location</th>
                    <th style="width: 30%;" class="text-right">Observations</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach($summary as $s)
                @if($type == 'attendance')
                    <tr>
                        <td class="text-center">#{{ $loop->iteration }}</td>
                        <td style="font-weight: bold;">{{ $s->guard_name }}</td>
                        <td class="text-center">{{ $s->present_days }} / {{ $s->total_days }}</td>
                        <td class="text-center">{{ $s->late_count }}</td>
                        <td class="text-right" style="font-weight: bold;">{{ $s->attendance_rate }}%</td>
                    </tr>
                @elseif($type == 'patrol' || $type == 'night_patrol')
                    <tr>
                        <td style="font-weight: bold;">{{ $s->guard_name }}</td>
                        <td class="text-center">{{ $s->total_sessions }}</td>
                        <td class="text-center">{{ $s->total_dist }} km</td>
                        <td class="text-center">{{ $s->avg_speed }} km/h</td>
                        <td class="text-center">{{ $s->total_time }}h</td>
                    </tr>
                @elseif($type == 'incident')
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($s->created_at)->format('d M y, H:i') }}</td>
                        <td style="font-weight: bold;">{{ $s->guard_name }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $s->type)) }}</td>
                        <td>{{ $s->site_name ?? 'N/A' }}</td>
                        <td class="text-right" style="font-size: 8px;">{{ $s->notes }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    {{-- View Map Button for single-day attendance --}}
    @if(isset($mapViewUrl) && $mapViewUrl)
        <div style="text-align: center; margin-bottom: 15px; padding: 12px; background: linear-gradient(135deg, #e8f4fd 0%, #d1ecff 100%); border: 1px solid #b3d9f7; border-radius: 6px;">
            <div style="font-size: 9px; color: #1a3a5a; font-weight: bold; margin-bottom: 6px; text-transform: uppercase;">
                Interactive Attendance Map Available
            </div>
            <a href="{{ $mapViewUrl }}" style="display: inline-block; background: #1a3a5a; color: #fff; text-decoration: none; padding: 8px 25px; border-radius: 20px; font-size: 10px; font-weight: bold; letter-spacing: 0.5px;">
                VIEW MAP — Click to Open Live Map
            </a>
            <div style="font-size: 7px; color: #64748b; margin-top: 5px;">
                Opens in your web browser with geofence visualization and attendance markers
            </div>
        </div>
    @endif

    {{-- Detailed Log Section --}}
    <div class="table-title">Granular Activity Audit Logs</div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Timestamp</th>
                <th style="width: 20%;">Security Personnel</th>
                <th style="width: 20%;">Deployment Area</th>
                @if($type == 'attendance')
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Location</th>
                    <th style="width: 10%;">Punch In</th>
                    <th style="width: 10%;">Punch Out</th>
                    <th style="width: 10%;">Late Mins</th>
                @elseif($type == 'incident')
                    <th style="width: 20%;">Category</th>
                    <th style="width: 25%;">Officer Observation</th>
                @else
                    <th style="width: 10%;">Mode</th>
                    <th style="width: 10%;">Distance</th>
                    <th style="width: 15%;">Duration</th>
                    <th style="width: 10%;">Efficiency</th>
                </tr>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>
                        @if($type == 'attendance') {{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}
                        @elseif($type == 'incident') {{ \Carbon\Carbon::parse($row->created_at)->format('d/m/y H:i') }}
                        @else {{ \Carbon\Carbon::parse($row->started_at)->format('d/m/y H:i') }} @endif
                    </td>
                    <td style="font-weight: bold;">{{ $row->guard_name }}</td>
                    <td>{{ $row->site_name ?? 'Beat-N/A' }}</td>
                    
                    @if($type == 'attendance')
                        <td>
                            <span class="badge {{ $row->status == 1 ? 'badge-success' : 'badge-danger' }}">
                                {{ $row->status == 1 ? 'PRESENT' : 'ABSENT' }}
                            </span>
                        </td>
                        <td style="font-weight: bold; color: {{ $row->inOutStatus == 'Inside' ? '#166534' : '#666' }};">
                            {{ $row->inOutStatus == 'Inside' ? 'ON SITE' : 'NOT MARKED' }}
                        </td>
                        <td style="font-weight: bold; color: #1a3a5a;">{{ $row->entry_time ?: '—' }}</td>
                        <td>{{ $row->exit_time ?: '—' }}</td>
                        <td>{{ $row->late_minutes > 0 ? $row->late_minutes.'m' : 'ON TIME' }}</td>
                    @elseif($type == 'incident')
                        <td>{{ ucwords(str_replace('_', ' ', $row->type)) }}</td>
                        <td style="font-size: 7px; color: #555;">{{ $row->notes ?: 'N/A' }}</td>
                    @else
                        <td>{{ $row->mode }}</td>
                        <td style="font-weight: bold; color: #1a3a5a;">{{ $row->distance_km }} km</td>
                        <td>{{ $row->duration_formatted }}</td>
                        <td>{{ $row->avg_speed }} <span style="font-size: 6px; color: #999;">KM/H</span></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        FSM AI Patrolling Management Solution | Official Use Only | Page 1 of 1
    </div>
</body>
</html>

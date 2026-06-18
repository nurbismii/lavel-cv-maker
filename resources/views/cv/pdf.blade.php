<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>CV {{ $profile->full_name }}</title>
    <style>
        @page {
            margin: 18mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .cv-paper {
            width: 100%;
            background: #ffffff;
        }

        .cv-output-header {
            padding-bottom: 11px;
            border-bottom: 2px solid #1f2937;
            margin-bottom: 14px;
        }

        .cv-output-header-grid {
            display: table;
            width: 100%;
        }

        .cv-output-header-main {
            display: table-cell;
            vertical-align: top;
            padding-right: 16px;
        }

        .cv-output-photo-frame {
            display: table-cell;
            width: 90px;
            height: 115px;
            border: 1.5px solid #111827;
            vertical-align: top;
            text-align: center;
            overflow: hidden;
        }

        .cv-output-photo-frame img {
            width: 90px;
            height: 115px;
        }

        .cv-output-header h1 {
            font-size: 20px;
            line-height: 1.15;
            letter-spacing: 0;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0 0 6px;
        }

        .cv-output-meta,
        .cv-output-contact {
            color: #374151;
            margin: 0 0 3px;
        }

        .cv-output-meta span,
        .cv-output-contact span {
            color: #9ca3af;
            margin: 0 4px;
        }

        .cv-output-section {
            margin-bottom: 13px;
            page-break-inside: avoid;
        }

        .cv-output-section h2 {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #1f2937;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
            margin: 0 0 8px;
        }

        .cv-output-section p {
            margin: 0 0 5px;
        }

        .cv-output-entry {
            margin-bottom: 9px;
            page-break-inside: avoid;
        }

        .cv-output-entry h3 {
            font-size: 11px;
            font-weight: 800;
            margin: 0 0 3px;
        }

        .cv-output-list {
            list-style: none;
            padding-left: 0;
            margin: 5px 0 0;
        }

        .cv-output-list li {
            position: relative;
            padding-left: 12px;
            margin-bottom: 3px;
        }

        .cv-output-list li::before {
            content: ">";
            position: absolute;
            left: 0;
            color: #111827;
            font-weight: 800;
        }

        .cv-output-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .cv-output-table th,
        .cv-output-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: middle;
        }

        .cv-output-table th {
            background: #f3f4f6;
            font-weight: 800;
        }
    </style>
</head>
<body>
    @include('cv.templates.hris', ['profile' => $profile, 'preview' => $preview, 'isPdf' => true])
</body>
</html>

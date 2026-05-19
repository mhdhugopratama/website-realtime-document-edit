<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document->title }}</title>
    <style>
        @page {
            margin: 2.5cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.7;
            color: #212121;
            background: white;
            margin: 0;
            padding: 0;
        }

        p  { margin: 0 0 9px 0; }

        h1 { font-size: 17pt; font-weight: bold; margin: 14px 0 7px; color: #111; }
        h2 { font-size: 14pt; font-weight: bold; margin: 12px 0 6px; color: #111; }
        h3 { font-size: 12pt; font-weight: bold; margin: 10px 0 5px; color: #111; }
        h4 { font-size: 11pt; font-weight: bold; margin: 8px 0 4px; color: #111; }

        strong, b { font-weight: bold; }
        em, i     { font-style: italic; }
        u         { text-decoration: underline; }
        s         { text-decoration: line-through; }

        ul, ol { margin: 6px 0 6px 22px; padding: 0; }
        li     { margin-bottom: 3px; }

        blockquote {
            border-left: 3px solid #4285f4;
            padding-left: 12px;
            color: #555;
            margin: 10px 0;
            font-style: italic;
        }

        code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9.5pt;
            background: #f5f5f5;
            padding: 1px 4px;
        }

        pre {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9.5pt;
            background: #f5f5f5;
            padding: 10px;
            margin: 8px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10.5pt;
        }

        th, td {
            border: 1px solid #cccccc;
            padding: 6px 9px;
            text-align: left;
        }

        th {
            background-color: #e8f0fe;
            font-weight: bold;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 14px 0;
        }

        figure { margin: 10px 0; }
        img    { max-width: 100%; }
    </style>
</head>
<body>
    @if($document->content)
        <div style="white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; font-size: 11pt;">{{ $document->content }}</div>
    @else
        <p style="color:#aaa; font-style:italic;">Dokumen ini masih kosong.</p>
    @endif
</body>
</html>

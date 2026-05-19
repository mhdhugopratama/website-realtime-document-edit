<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document->title }}</title>

    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->

    <style>

        @page WordSection1 {
            size: 21cm 29.7cm;
            margin: 2.5cm 2.5cm 2.5cm 2.5cm;
            mso-header-margin: 1cm;
            mso-footer-margin: 1cm;
        }

        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.8;
            color: #212121;
            div: WordSection1;
        }


        .doc-header {
            border-bottom: 2pt solid #4285f4;
            padding-bottom: 10pt;
            margin-bottom: 20pt;
        }

        .doc-title {
            font-size: 20pt;
            font-weight: bold;
            color: #1a73e8;
            margin-bottom: 6pt;
        }

        .doc-meta {
            font-size: 9pt;
            color: #888888;
        }


        .doc-content {
            font-size: 12pt;
            line-height: 1.8;
        }

        .doc-content h1 { font-size: 18pt; font-weight: bold; margin: 14pt 0 7pt; }
        .doc-content h2 { font-size: 15pt; font-weight: bold; margin: 12pt 0 6pt; }
        .doc-content h3 { font-size: 13pt; font-weight: bold; margin: 10pt 0 5pt; }
        .doc-content p  { margin-bottom: 8pt; }

        .doc-content ul, .doc-content ol { margin: 6pt 0 6pt 20pt; }
        .doc-content li { margin-bottom: 3pt; }

        .doc-content blockquote {
            border-left: 4pt solid #4285f4;
            padding-left: 12pt;
            color: #555555;
            margin: 10pt 0;
            font-style: italic;
        }

        .doc-content code,
        .doc-content pre {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            background-color: #f5f5f5;
        }

        .doc-content pre {
            padding: 10pt;
            margin: 8pt 0;
        }

        .doc-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 10pt 0;
        }

        .doc-content th,
        .doc-content td {
            border: 1pt solid #dddddd;
            padding: 5pt 8pt;
            font-size: 11pt;
        }

        .doc-content th {
            background-color: #f0f4ff;
            font-weight: bold;
        }

        .doc-content hr {
            border-top: 1pt solid #dddddd;
            margin: 14pt 0;
        }


        .doc-footer {
            border-top: 1pt solid #eeeeee;
            margin-top: 28pt;
            padding-top: 8pt;
            font-size: 9pt;
            color: #aaaaaa;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="doc-header">
    <div class="doc-title">{{ $document->title }}</div>
    <div class="doc-meta">
        Dibuat oleh: {{ $document->owner->name }}
        &nbsp;&bull;&nbsp;
        Terakhir diperbarui: {{ $document->updated_at->format('d F Y, H:i') }}
        &nbsp;&bull;&nbsp;
        Diekspor dari GoDocs
    </div>
</div>

<div class="doc-content">
    @if($document->content)
        {!! $document->content !!}
    @else
        <p style="color:#aaaaaa; font-style:italic;">Dokumen ini masih kosong.</p>
    @endif
</div>

<div class="doc-footer">
    GoDocs &mdash; Diekspor pada {{ now()->format('d F Y, H:i') }}
</div>
</body>
</html>

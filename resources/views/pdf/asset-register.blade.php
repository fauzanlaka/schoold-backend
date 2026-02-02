<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ทะเบียนคุมทรัพย์สิน - {{ $asset->asset_name }}</title>
    <style>
        body {
            line-height: 1.2;
        }

        h4 {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .header-right {
            text-align: right;
            margin-bottom: 5px;
        }

        .header-row {
            margin-bottom: 3px;
            /* text-align: left; */
        }

        .check-groups {
            margin: 10px 0;
            padding: 5px 0;
        }

        .check-group {
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .group-label {
            font-weight: bold;
            margin-right: 15px;
        }

        .checkbox-item {
            margin-right: 20px;
            display: inline-block;
        }

        .reg-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .reg-table th,
        .reg-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            line-height: 1.3;
        }

        .reg-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .reg-table .td-center {
            text-align: center;
        }

        .reg-table .td-right {
            text-align: right;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            line-height: 0.5cm;
        }

        .info-table td {
            padding: 2px 2px;
            vertical-align: bottom;
            /* border: 1px solid #000; */
        }
    </style>
</head>

<body>
    <h4>ทะเบียนคุมทรัพย์สิน</h4>

    {{-- <div class="header-right">
        <div class="header-row">
            ส่วนราชการ {{ $schoolName ?? '' }} .........................................................
        </div>
        <div class="header-row">
            ............................................................
        </div>
    </div> --}}



    <table class="info-table">
        <tr>
            <td width="75%" style="text-align: right; font-weight: bold">
                ส่วนราชการ
            </td>
            <td width="25%" style="text-align: center;">{{ $schoolName ?? '' }}</td>
        </tr>
        <tr>
            <td width="75%"></td>
            <td style="padding-top: -19px;">....................................................................</td>
        </tr>
        <tr>
            <td width="75%" style="text-align: right; font-weight: bold; padding-right: 13px">
                หน่วยงาน
            </td>
            <td width="25%" style="text-align: center;">{{ $schoolName ?? '' }}
            </td>
        </tr>
        <tr>
            <td width="75%" style="padding-top: -19px; text-align: right; padding-right: -2px">....</td>
            <td style="padding-top: -19px;">....................................................................</td>
        </tr>
    </table>
    <table class="info-table">
        <tr>
            <td style="font-weight: bold" width="3%">ประเภท</td>
            <td width="25%" style="text-align: center;">{{ $asset->category->category_name ?? '-' }}</td>
            <td style="font-weight: bold" width="2%">รหัส</td>
            <td width="13%" style="text-align: center;">{{ $asset->asset_code }}</td>
            <td style="font-weight: bold" width="10%">ลักษณะ/คุณสมบัติ</td>
            <td width="25%" style="text-align: center;">{{ $asset->asset_name }}</td>
        </tr>
        <tr>
            <td></td>
            <td style="padding-top: -19px;">
                ...............................................................................
            </td>
            <td></td>
            <td style="padding-top: -19px;">
                ...........................................................................................
            </td>
            <td></td>
            <td style="padding-top: -19px;">
                ....................................................................
            </td>
        </tr>
    </table>
    <table class="info-table">
        <tr>
            <td style="font-weight: bold" width="19%">สถานที่ตั้ง/หน่วยงานที่รับผิดชอบ</td>
            <td style="text-align: center;" width="25%">{{ $schoolName ?? '-' }}</td>
            <td style="font-weight: bold;" width="12%">เลขทะเบียน GFMIS</td>
            <td style="font-weight: bold; text-align: center;" width="10%">{{ $asset->gfmis_number ?? '-' }}</td>
            <td style="font-weight: bold" width="16%">ชื่อผู้ขาย/ผู้รับจ้าง/ผู้บริจาค</td>
            <td style="text-align: center;">{{ $asset->supplier_name ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td style="padding-top: -19px;">
                ............................................................................
            </td>
            <td></td>
            <td style="padding-top: -19px;">
                .............................
            </td>
            <td></td>
            <td style="padding-top: -19px;">
                ..............................................
            </td>
        </tr>
    </table>
    <table class="info-table">
        <tr>
            <td style="font-weight: bold;" width="3%">ที่อยู่</td>
            <td style="text-align: center;" width="40%">{{ $schoolAddress ?? '-' }}</td>
            <td style="font-weight: bold;" width="6%">โทรศัพท์</td>
            <td style="font-weight: bold; text-align: center;" width="11%">{{ $asset->supplier_phone ?? '-' }}</td>
            <td style="font-weight: bold" width="10%">Serial Number</td>
            <td style="text-align: center;">{{ $asset->supplier_name ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td style="padding-top: -19px;">
                ..........................................................................................................................
            </td>
            <td></td>
            <td style="padding-top: -19px;">
                ................................
            </td>
            <td></td>
            <td style="padding-top: -19px;">
                ....................................................................................
            </td>
        </tr>
    </table>
    {{-- <table class="info-table">
        <tr>
            <td colspan="6" style="padding-top: -20px;">
                ......................................................................................................
                ......................................................................................................
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold" colspan="2">ชื่อผู้ขาย/ผู้รับจ้าง/ผู้บริจาค</td>
            <td colspan="4">{{ $asset->supplier_name ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="6" style="padding-top: -20px;">
                ......................................................................................................
                ......................................................................................................
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold">ที่อยู่</td>
            <td colspan="3">-</td>
            <td style="font-weight: bold">โทรศัพท์</td>
            <td>{{ $asset->supplier_phone ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="6" style="padding-top: -20px;">
                ......................................................................................................
                ......................................................................................................
            </td>
        </tr>
    </table> --}}

    {{-- budget_type and acquisition_method --}}

    {{-- Budget Type Row --}}
    <table class="info-table" style="table-layout: fixed;">
        <tr>
            <td style="font-weight: bold; width: 10%;">ประเภทเงิน</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->budget_type == 1 ? '/' : ' ' }}] เงินงบประมาณ</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->budget_type == 2 ? '/' : ' ' }}] เงินนอกงบประมาณ</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->budget_type == 3 ? '/' : ' ' }}] เงินบริจาค/เงินช่วยเหลือ</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->budget_type == 4 ? '/' : ' ' }}] อื่นๆ</td>
            <td style="width: 18%;"></td>
        </tr>
    </table>

    {{-- Acquisition Method Row --}}
    <table class="info-table" style="table-layout: fixed;">
        <tr>
            <td style="font-weight: bold; width: 10%;">วิธีการได้มา</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->acquisition_method == 1 ? '/' : ' ' }}] วิธีเฉพาะเจาะจง</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->acquisition_method == 2 ? '/' : ' ' }}] วิธีคัดเลือก</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->acquisition_method == 3 ? '/' : ' ' }}] วิธีสอบราคา</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->acquisition_method == 4 ? '/' : ' ' }}] วิธีพิเศษ</td>
            <td style="text-align: left; width: 18%;">[{{ $asset->acquisition_method == 5 ? '/' : ' ' }}] รับบริจาค</td>
        </tr>
    </table>

    <table class="reg-table">
        <thead>
            <tr>
                <th style="width: 70px;">วัน เดือน ปี</th>
                <th style="width: 80px;">ที่เอกสาร</th>
                <th>รายการ</th>
                <th style="width: 50px;">จำนวนหน่วย</th>
                <th style="width: 80px;">ราคาต่อหน่วย</th>
                <th style="width: 80px;">มูลค่ารวม</th>
                <th style="width: 50px;">อายุใช้งาน</th>
                <th style="width: 50px;">อัตราค่าเสื่อม</th>
                <th style="width: 80px;">ค่าเสื่อมราคา<br />ประจำปี</th>
                <th style="width: 80px;">ค่าเสื่อมราคา<br />สะสม</th>
                <th style="width: 80px;">มูลค่าสุทธิ</th>
            </tr>
        </thead>
        <tbody>
            <!-- First Row: Initial Asset Entry -->
            <tr>
                <td class="td-center">{{ $purchaseDateFormatted }}</td>
                <td class="td-center">{{ $asset->document_number ?? '-' }}</td>
                <td>{{ $asset->asset_name }}</td>
                <td class="td-center">{{ $asset->quantity }}</td>
                <td class="td-right">{{ number_format($asset->unit_price, 2) }}</td>
                <td class="td-right">{{ number_format($totalValue, 2) }}</td>
                <td class="td-center">{{ $usefulLife }}</td>
                <td class="td-center">{{ $depreciationRate }}%</td>
                <td class="td-right">{{ number_format($annualDepreciation, 2) }}</td>
                <td class="td-right">-</td>
                <td class="td-right">{{ number_format($totalValue, 2) }}</td>
            </tr>

            <!-- Depreciation Rows -->
            @foreach($depreciationRows as $row)
                <tr>
                    <td class="td-center">{{ $row['date'] }}</td>
                    <td class="td-center">-</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="td-center"></td>
                    <td class="td-right"></td>
                    <td class="td-right"></td>
                    <td class="td-center"></td>
                    <td class="td-center"></td>
                    <td class="td-right">{{ number_format($row['depreciation'], 2) }}</td>
                    <td class="td-right">{{ number_format($row['accumulated'], 2) }}</td>
                    <td class="td-right">{{ number_format($row['netValue'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
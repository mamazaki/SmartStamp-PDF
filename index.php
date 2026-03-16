<?php
/**
 * ระบบลงรับหนังสืออิเล็กทรอนิกส์ (Stamp PDF)
 * ออกแบบและพัฒนาโดย นายสุทธิชัย ชมชื่น
 * สำนักงานศึกษาธิการจังหวัดอุดรธานี
 */

$config = [
    "officeName" => "สำนักงานศึกษาธิการจังหวัดอุดรธานี",
    "developer" => [
        "name"     => "นายสุทธิชัย ชมชื่น",
        "position" => "นักวิชาการคอมพิวเตอร์ชำนาญการ",
        "office"   => "สำนักงานศึกษาธิการจังหวัดอุดรธานี"
    ],
    "groups" => [
        ["อำนวยการ", "บริหารงานบุคคล", "นโยบายและแผน"],
        ["พัฒนาการศึกษา", "นิเทศฯ", "ส่งเสริมการศึกษาเอกชน"],
        ["ลูกเสือฯ", "หน่วยตรวจสอบภายใน", "คุรุสภา"]
    ],
    "pdfSettings" => [
        "fontSizeMain"  => 14,
        "fontSizeGroup" => 12,
        "groupSpacing"  => 15,
        "padding"       => 10,
        "boxH"          => 98
    ]
];
$jsonConfig = json_encode($config);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ระบบลงรับหนังสือ - <?php echo $config['officeName']; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>
    <script src="https://unpkg.com/@pdf-lib/fontkit/dist/fontkit.umd.min.js"></script>

    <style>
        @font-face { font-family: 'StampFont'; src: url('THSarabunNew.ttf') format('truetype'); }
        :root { --navy: #000080; --border-color: #d1d9e6; }
        body { font-family: 'Sarabun', sans-serif; margin: 0; display: flex; flex-direction: row; height: 100vh; background-color: #f0f2f5; overflow: hidden; }
        
        .sidebar { width: 350px; background: white; box-shadow: 2px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; z-index: 100; }
        .sidebar-header { padding: 20px; background: var(--navy); color: white; text-align: center; }
        .sidebar-header h2 { margin: 0; font-size: 1rem; line-height: 1.4; }
        .sidebar-content { padding: 15px; flex: 1; overflow-y: auto; }
        
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 700; margin-bottom: 8px; color: #333; font-size: 0.9rem; }
        input[type="text"], select, input[type="file"], input[type="range"] { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Sarabun', sans-serif; box-sizing: border-box; }
        
        .button-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }
        .btn { border: none; padding: 15px; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; color: white; transition: 0.3s; }
        .btn-download { background: var(--navy); grid-column: span 2; }
        .btn-print { background: #28a745; }
        .btn-clear { background: #dc3545; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }

        .sidebar-footer { padding: 15px; background: #f8f9fa; border-top: 1px solid #eee; font-size: 0.75rem; color: #666; text-align: center; }

        #stamp-preview { 
            position: absolute; border: 1.2px solid rgba(0,0,128,0.7); background: rgba(255,255,255,0.98); 
            padding: 10px; pointer-events: none; display: none; color: rgba(0,0,128,1); 
            font-family: 'StampFont', serif !important; font-size: 14px; line-height: 1.3; 
            white-space: nowrap; border-radius: 2px; z-index: 10; transform-origin: top left;
        }

        .main-view { flex: 1; padding: 10px; overflow: auto; display: flex; flex-direction: column; align-items: center; position: relative; }
        .pdf-container-wrapper { position: relative; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 10px; }
        canvas { display: block; cursor: crosshair; max-width: 100%; height: auto !important; }

        @media (max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; max-height: 55vh; } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>ระบบลงรับหนังสือ<br><?php echo $config['officeName']; ?></h2>
    </div>
    <div class="sidebar-content">
        <div class="form-group">
            <label>1. เลือกไฟล์ PDF</label>
            <input type="file" id="file-input" accept="application/pdf">
        </div>
        <div class="form-group">
            <label>2. เลขที่รับ</label>
            <input type="text" id="pmss-no" placeholder="ระบุเลขที่รับ">
        </div>
        <div class="form-group">
            <label>3. กลุ่มงาน</label>
            <select id="group-select" onchange="updatePreview()">
                <?php foreach ($config['groups'] as $row) foreach ($row as $g) echo "<option value=\"$g\">$g</option>"; ?>
            </select>
        </div>
        <div class="form-group">
            <label>4. ปรับขนาด Stamp (<span id="scale-val">100</span>%)</label>
            <input type="range" id="stamp-scale" min="50" max="150" value="100" oninput="updatePreview()">
        </div>

        <button class="btn btn-download" id="download-btn" onclick="generatePdf('download')" disabled>บันทึกและดาวน์โหลด</button>
        <div class="button-group">
            <button class="btn btn-print" id="print-btn" onclick="generatePdf('print')" disabled>พิมพ์เอกสาร</button>
            <button class="btn btn-clear" onclick="location.reload()">ล้างค่า</button>
        </div>
    </div>
    <div class="sidebar-footer">
        ออกแบบและพัฒนาโดย<br><b><?php echo $config['developer']['name']; ?></b><br>
        <?php echo $config['developer']['position']; ?>
    </div>
</div>

<div class="main-view">
    <div class="pdf-container-wrapper" id="pdf-wrapper">
        <canvas id="pdf-canvas"></canvas>
        <div id="stamp-preview"></div>
    </div>
</div>

<script>
    const appConfig = <?php echo $jsonConfig; ?>;
    const { PDFDocument, rgb } = PDFLib;
    let originalBuffer = null;
    let stampPos = { x: 0, y: 0 };
    let thaiFontBytes = null;
    let currentScale = 1;

    (async () => {
        try {
            const response = await fetch('THSarabunNew.ttf');
            thaiFontBytes = await response.arrayBuffer();
            document.getElementById('download-btn').disabled = false;
            document.getElementById('print-btn').disabled = false;
        } catch (err) { console.error("Font error"); }
    })();

    function getThaiDateTime() {
        const now = new Date();
        const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        return { 
            date: `${now.getDate().toString().padStart(2,'0')} ${months[now.getMonth()]} ${(now.getFullYear()+543).toString().slice(-2)}`,
            time: `${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')} น.`
        };
    }

    document.getElementById('file-input').addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        originalBuffer = await file.arrayBuffer();
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
        const loadingTask = pdfjsLib.getDocument({ data: originalBuffer.slice(0) });
        const pdfJsDoc = await loadingTask.promise;
        const page = await pdfJsDoc.getPage(1);
        currentScale = window.innerWidth < 768 ? (window.innerWidth - 40) / page.getViewport({scale:1}).width : 1.3;
        const viewport = page.getViewport({ scale: currentScale });
        const canvas = document.getElementById('pdf-canvas');
        canvas.width = viewport.width; canvas.height = viewport.height;
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    });

    function updatePreview() {
        if (!originalBuffer || stampPos.x === 0) return;
        const dt = getThaiDateTime();
        const preview = document.getElementById('stamp-preview');
        const userScale = document.getElementById('stamp-scale').value / 100;
        document.getElementById('scale-val').innerText = document.getElementById('stamp-scale').value;
        
        const selectedGroup = document.getElementById('group-select').value;
        let gridHtml = "";
        appConfig.groups.forEach(row => {
            gridHtml += row.map(g => (g === selectedGroup ? '☑' : '☐') + ` ${g}`).join('&nbsp;&nbsp;') + "<br>";
        });

        preview.style.display = 'block';
        preview.style.left = stampPos.x + 'px';
        preview.style.top = stampPos.y + 'px';
        preview.style.transform = `scale(${(currentScale / 1.3) * userScale})`;
        preview.innerHTML = `<div style="font-weight:bold; margin-bottom:2px;">${appConfig.officeName}</div><div style="margin-bottom:4px;">เลขที่รับ: ${document.getElementById('pmss-no').value || '...'}  วันที่ ${dt.date}  เวลา ${dt.time}</div>${gridHtml}`;
    }

    document.getElementById('pdf-canvas').addEventListener('mousedown', (e) => {
        const rect = e.target.getBoundingClientRect();
        stampPos.x = e.clientX - rect.left;
        stampPos.y = e.clientY - rect.top;
        updatePreview();
    });

    async function generatePdf(action) {
        const receiptNo = document.getElementById('pmss-no').value || '...';
        if (!originalBuffer || stampPos.x === 0) return alert('เลือกจุดวางก่อน');
        try {
            const pdfDocLib = await PDFDocument.load(originalBuffer.slice(0));
            pdfDocLib.registerFontkit(fontkit);
            const customFont = await pdfDocLib.embedFont(thaiFontBytes);
            const page = pdfDocLib.getPages()[0];
            const { width, height } = page.getSize();
            const canvas = document.getElementById('pdf-canvas');
            const pdfX = stampPos.x * (width / canvas.width);
            const pdfY = height - (stampPos.y * (height / canvas.height));
            
            const userScale = document.getElementById('stamp-scale').value / 100;
            const s = appConfig.pdfSettings;
            const fontSizeMain = s.fontSizeMain * userScale;
            const fontSizeGroup = s.fontSizeGroup * userScale;
            const groupSpacing = s.groupSpacing * userScale;
            const padding = s.padding * userScale;
            const boxH = s.boxH * userScale;

            const dt = getThaiDateTime();
            const selectedGroup = document.getElementById('group-select').value;
            const navy = rgb(0, 0, 0.5, 0.8);

            const line1 = appConfig.officeName;
            const line2 = `เลขที่รับ: ${receiptNo}  วันที่ ${dt.date}  เวลา ${dt.time}`;
            
            let maxRowW = 0;
            appConfig.groups.forEach(row => {
                let rowW = 0;
                row.forEach((g, i) => {
                    rowW += customFont.widthOfTextAtSize("☐ " + g, fontSizeGroup);
                    if (i < row.length - 1) rowW += groupSpacing;
                });
                if (rowW > maxRowW) maxRowW = rowW;
            });

            const maxW = Math.max(customFont.widthOfTextAtSize(line1, fontSizeMain), customFont.widthOfTextAtSize(line2, fontSizeMain), maxRowW);
            const boxW = maxW + (padding * 2);

            page.drawRectangle({ x: pdfX, y: pdfY - boxH, width: boxW, height: boxH, borderColor: navy, borderWidth: 1.2 * userScale });
            page.drawText(line1, { x: pdfX + padding, y: pdfY - (20 * userScale), size: fontSizeMain, font: customFont, color: navy });
            page.drawText(line2, { x: pdfX + padding, y: pdfY - (40 * userScale), size: fontSizeMain, font: customFont, color: navy });

            appConfig.groups.forEach((row, rowIndex) => {
                let currentOffsetX = padding;
                const itemY = pdfY - (62 * userScale) - (rowIndex * (16 * userScale)); 
                row.forEach(name => {
                    page.drawRectangle({ x: pdfX + currentOffsetX, y: itemY, width: 8 * userScale, height: 8 * userScale, borderColor: navy, borderWidth: 0.8 * userScale });
                    if (name === selectedGroup) {
                        page.drawLine({ 
                            start: { x: pdfX + currentOffsetX + (1 * userScale), y: itemY + (4.5 * userScale) }, 
                            end: { x: pdfX + currentOffsetX + (3.5 * userScale), y: itemY + (1.5 * userScale) }, 
                            color: navy, thickness: 1.4 * userScale 
                        });
                        page.drawLine({ 
                            start: { x: pdfX + currentOffsetX + (3.5 * userScale), y: itemY + (1.5 * userScale) }, 
                            end: { x: pdfX + currentOffsetX + (7.5 * userScale), y: itemY + (6.5 * userScale) }, 
                            color: navy, thickness: 1.4 * userScale 
                        });
                    }
                    page.drawText(name, { x: pdfX + currentOffsetX + (12 * userScale), y: itemY, size: fontSizeGroup, font: customFont, color: navy });
                    currentOffsetX += customFont.widthOfTextAtSize("☐ " + name, fontSizeGroup) + groupSpacing;
                });
            });

            const pdfBytes = await pdfDocLib.save();
            const blob = new Blob([pdfBytes], { type: "application/pdf" });
            const url = URL.createObjectURL(blob);
            
            if (action === 'download') {
                const link = document.createElement('a');
                link.href = url;
                link.download = `หนังสือลงรับ เลขที่ ${receiptNo}.pdf`;
                link.click();
            } else {
                const printWindow = window.open(url);
                printWindow.print();
            }
        } catch (err) { alert(err.message); }
    }
</script>
</body>
</html>
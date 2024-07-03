<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Include FPDF library
require('C:\xampp\htdocs\project\Requestform\userAuth\fpdf186\fpdf.php'); // Update this path to the correct location of your FPDF library

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "verite";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

class PDF extends FPDF {
    private $widths;
    private $company;

    function __construct($company) {
        parent::__construct();
        $this->company = $company;
    }

    function Header() {
        $this->SetFont('Arial', 'B', 12);
        // Selecting logo based on company selection
        $basePath = 'C:\xampp\htdocs\project\Requestform\userAuth\images\\';
        $logoPath = $basePath . 'ITEC_LOGO.png'; // Default logo path

        if ($this->company == 'ITTCO') {
            $logoPath = $basePath . 'ITTCO_LOGO.png'; // Update with actual path
        } elseif ($this->company == 'G.E.P.S') {
            $logoPath = $basePath . 'GEPS_LOGO.jpg'; // Update with actual path
        }

        if (!file_exists($logoPath)) {
            throw new Exception("Logo file does not exist: $logoPath");
        }

        $this->Image($logoPath, ($this->GetPageWidth() - 50) / 2, 7, 80); // Adjust position and size as needed

        // Add a line break to position the title below the logo
        $this->Ln(13); // Adjust the value to move the title below the logo

        // Center the title
        $this->Cell(0, 10, 'Requisition Form', 0, 1, 'C');
        $this->Ln(10);
    }

    function SetWidths($w) {
        // Set the column widths
        $this->widths = $w;
    }

    function Row($data) {
        // Calculate the height of the row
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = 5 * $nb;

        // Issue a page break first if needed
        $this->CheckPageBreak($h);

        // Draw the cells
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }

        // Go to the next line
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        // If the height h would cause an overflow, add a new page
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt) {
        // Calculate the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projectname = $_POST['projectname'];
    $rqst_title = $_POST['rqst_title'];
    $products = $_POST['product'];
    $otherProducts = $_POST['otherProduct'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['price'];
    $descriptions = $_POST['description'];
    $return_document = $_POST['return_document'];
    $other_document = isset($_POST['other_document']) ? $_POST['other_document'] : ''; // Other return document
    $rqst_by = $_SESSION['user_id'];
    $company = $_POST['company'];

    // Handle file upload for request table
    $fileContent = null;
    $hasSupportingDoc = 'no';
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $fileTmpName = $_FILES['file']['tmp_name'];
        $fileContent = file_get_contents($fileTmpName);
        $hasSupportingDoc = 'yes';
    }

    // Insert the request details into the request table
    $requestSql = $conn->prepare("INSERT INTO request (rqst_time, rqst_title, projectname, rqst_by, file_column, has_supporting_doc, return_document, credited_company) VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?)");
    $requestSql->bind_param("ssissss", $rqst_title, $projectname, $rqst_by, $fileContent, $hasSupportingDoc, $return_document, $company);

    if ($requestSql->execute()) {
        $rqst_id = $requestSql->insert_id; // Get the ID of the inserted request
        $requestSql->close();

        $productsData = [];
        foreach ($products as $index => $product) {
            if ($product == 'other') {
                $product = $otherProducts[$index];
            }

            $quantity = $quantities[$index];
            $price = $prices[$index];
            $description = $descriptions[$index];

            // Prepare SQL statement to insert the product details into the product table
            $productSql = $conn->prepare("INSERT INTO product (product_name, Quantity, price_per_unit, description) VALUES (?, ?, ?, ?)");
            $productSql->bind_param("sids", $product, $quantity, $price, $description);

            // Execute the SQL statement
            if ($productSql->execute()) {
                $product_id = $productSql->insert_id;

                // Link product with request
                $linkSql = $conn->prepare("INSERT INTO request_product (rqst_id, product_id) VALUES (?, ?)");
                $linkSql->bind_param("ii", $rqst_id, $product_id);
                $linkSql->execute();
                $linkSql->close();

                $productsData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $quantity * $price,
                    'description' => $description
                ];

                echo 'Product inserted successfully.<br>';
            } else {
                echo 'Error: ' . $productSql->error . '<br>';
            }

            // Close the prepared statement
            $productSql->close();
        }
       
        // Generate PDF
        $pdf = new PDF($company);
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);

        $pdf->Cell(0, 10, "Requested By: " . $_SESSION['user_name'], 0, 1); 
        $pdf->Cell(0, 10, "Project Name: $projectname", 0, 1);
        $pdf->Cell(0, 10, "Requisition Title: $rqst_title", 0, 1);
        // Using the session variable for the user's name
        $pdf->Cell(0, 10, "Return Document: $return_document", 0, 1); // Include return document in PDF
        if ($return_document == 'Other') {
            $pdf->Cell(0, 10, "Other Document: $other_document", 0, 1);
        }
        $pdf->Ln(10);

        // Table header
        $pdf->SetWidths([10, 40, 50, 20, 30, 30]);
        $pdf->Row(['Nbr', 'Product Name', 'Product Description', 'QTY', 'Unit Price', 'Total Price']);

        // Table data
        foreach ($productsData as $index => $productData) {
            $pdf->Row([
                $index + 1,
                $productData['product'],
                $productData['description'],
                $productData['quantity'],
                number_format($productData['price'], 2),
                number_format($productData['total'], 2)
            ]);
        }

        $pdfContent = $pdf->Output('S'); // Get the PDF content as a string

        // Update the request with the PDF content
        $updatePdfSql = $conn->prepare("UPDATE request SET pdf_view = ? WHERE rqst_id = ?");
        $updatePdfSql->bind_param("bi", $pdfContent, $rqst_id);
        $updatePdfSql->send_long_data(0, $pdfContent);
        if ($updatePdfSql->execute()) {
            echo 'PDF updated successfully.<br>';
        } else {
            echo 'Error updating PDF: ' . $updatePdfSql->error . '<br>';
        }
        $updatePdfSql->close();

        // Display link to view the PDF and cancel button
        echo '<a href="onrequest_view_pdf.php?id=' . $rqst_id . '" target="_blank">View PDF</a><br>';
        echo '<form method="post" action="cancel_request.php">';
        echo '<input type="hidden" name="rqst_id" value="' . $rqst_id . '">';
        echo '<button type="submit">Cancel</button>';
        echo '</form>';

    } else {
        echo 'Error: ' . $requestSql->error . '<br>';
        $requestSql->close();
    }
}

// Close the database connection
$conn->close();
?>

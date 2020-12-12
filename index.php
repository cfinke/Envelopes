<?php

if ( isset( $_POST['envelopes_submit'] ) ) {
	require "tcpdf/tcpdf.php";
	
	$envelope_width = $_POST['envelopes_width'];
	$envelope_height = $_POST['envelopes_height'];
	
	switch ( $_POST['envelopes_width_unit'] ) {
		case 'in':
			// Assumed
		break;
		case 'cm':
			$envelope_width /= 2.54;
		break;
	}
	
	switch ( $_POST['envelopes_height_unit'] ) {
		case 'in':
			// Assumed
		break;
		case 'cm':
			$envelope_height /= 2.54;
		break;
	}
	
	$page_width = $envelope_width * 72; // pts
	$page_height = $envelope_height * 72; // pts

	// If you tell TCPDF that you want portrait orientation, it will automatically use the longer value of the size array as the height.
	$orientation = 'P';
	
	if ( $page_width > $page_height ) {
		$orientation = 'L';
	}

	$pdf = new TCPDF( $orientation, 'pt', array( $page_width, $page_height ) );
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

	$pdf->SetMargins( 0, 0, 0 );
	$pdf->SetAutoPageBreak( false );

	$csv_as_array = array();
	
	$file = fopen( $_FILES['envelopes_csv']['tmp_name'], "r" );
	
	$header_line = fgetcsv( $file );
	$header_line = array_map( 'trim', $header_line );
	
	$column_index_to_column_name = array();
		
	foreach ( $header_line as $idx => $column_name ) {
		$column_index_to_column_name[ $column_name ] = $idx;
	}

	while ( ! feof( $file ) && $line = fgetcsv( $file ) ) {
		$array_for_this_line = array();
		
		foreach ( $line as $idx => $value ) {
			$column_index = $idx;
			$column_name = $header_line[ $column_index ];
			
			$array_for_this_line[ $column_name ] = $value;
		}
		
		$csv_as_array[] = $array_for_this_line;
	}
	
	fclose( $file );

	$margin = 10;
	
	$return_address_line_height = max( 1, intval( $_POST['envelopes_return_address_font_size'] ) );
	$return_address_line_padding = 2;
	
	$to_address_line_height = max( 1, intval( $_POST['envelopes_to_address_font_size'] ) );

	foreach ( $csv_as_array as $entry ) {
		$pdf->SetPrintHeader(false);
		$pdf->SetPrintFooter(false);
		$pdf->AddPage();

		// Add return address.
		$x = $margin;
		$y = $margin;

		$address = $_POST['envelopes_format'];

		foreach ( $entry as $column => $value ) {
			$address = str_replace( '{{' . $column . '}}', trim( $value ), $address );
		}

		$address = preg_replace( '/\n(\s*)\n+/', "\n", $address );
		$address = trim( $address );
		
		if ( ! empty( $_FILES['envelopes_image']['size'] ) ) {
			$pdf->SetXY( $x, $y );
			
			$image_size = getimagesize( $_FILES['envelopes_image']['tmp_name'] );
			
			$height = ( $return_address_line_height * 3 ) + ( $return_address_line_padding * 2 );
			$width = $height * ( $image_size[0] / $image_size[1] );
			
			$pdf->Image( $_FILES['envelopes_image']['tmp_name'], $x, $y, $width, $height );
			$x += $width + ( $margin / 2 );
		}

		$pdf->SetXY( $x, $y );
		$pdf->SetFont( 'dejavusans', '', $return_address_line_height );
		$pdf->MultiCell( $page_width, $return_address_line_height, $_POST['envelopes_return_address'], 0, "L" );

		// Location for the to: address.
		if ( 'P' == $orientation ) {
			$x = $page_width * 0.30;
		}
		else {
			$x = $page_width * 0.4;
		}

		$y = $page_height * 0.45;
		
		$pdf->SetXY( $x, $y );
		$pdf->SetFont( 'dejavusans', '', $to_address_line_height );
		$pdf->MultiCell( $page_width / 2, $to_address_line_height, $address, 0, "L" );
	}
	
	$pdf->Output();

	die;
}

?>
<!doctype html>
<html>
	<head>
		<title>Upload a CSV, get a PDF of envelopes to print</title>
	</head>
	<body>
		<h1>Envelopes</h1>
		<form method="post" action="" enctype="multipart/form-data">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="envelopes_width">Envelope Width</label>
						</th>
						<td>
							<input type="text" name="envelopes_width" id="envelopes_width" />
							<select name="envelopes_width_unit">
								<option value="in">Inches</option>
								<option value="cm">Centimeters</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="envelopes_height">Envelope Height</label>
						</th>
						<td>
							<input type="text" name="envelopes_height" id="envelopes_height" />
							<select name="envelopes_height_unit">
								<option value="in">Inches</option>
								<option value="cm">Centimeters</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="envelopes_return_address">Return Address</label>
						</th>
						<td>
							<textarea name="envelopes_return_address" id="envelopes_return_address"></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="envelopes_return_address_font_size">Return Address Font Size</label>
						</th>
						<td>
							<input type="number" name="envelopes_return_address_font_size" value="12" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="envelopes_image">Return Address Image</label>
						</th>
						<td>
							<input type="file" id="envelopes_image" name="envelopes_image" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="envelopes_format">Format</label>
							<p>Example:</p>
							<p><code>{{First Name}} {{Last Name}}<br />{{Address}}<br />{{City}}, {{State}} {{Zip}}</code></p>
						</th>
						<td>
							<textarea name="envelopes_format" id="envelopes_format"></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="envelopes_return_address">CSV</label>
						</th>
						<td>
							<input type="file" id="envelopes_csv" name="envelopes_csv" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="envelopes_to_address_font_size">To: Address Font Size</label>
						</th>
						<td>
							<input type="number" name="envelopes_to_address_font_size" value="12" />
						</td>
					</tr>
				</tbody>
			</table>
			<p>
			<input type="submit" class="button button-primary" name="envelopes_submit" value="Generate PDF" />
		</form>
	</body>
</html>
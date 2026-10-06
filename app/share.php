<?php
function getImageBlob($imageUrl) {
    $imageData = file_get_contents($imageUrl);
    if ($imageData !== false) {
        $imageBlob = base64_encode($imageData);
        return $imageBlob;
    } else {
        return null;
    }
}

if (isset($_GET['imageUrl'])) {
    $imageUrl = $_GET['imageUrl'];
    $imageBlob = getImageBlob($imageUrl);
    echo $imageBlob;
}
?>
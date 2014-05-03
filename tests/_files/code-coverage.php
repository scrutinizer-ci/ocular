<?php


$response = array(
//     'request'=>$_GET,
//     'post'=>$_POST,
//     'inarray'=>in_array(md5('success'), $_REQUEST['access_token']),
//     'is1'=>md5('success') === $_REQUEST['access_token'][0],
//     'is2'=>md5('success') === $_REQUEST['access_token'][1]
);

if (!in_array(md5('success'), $_REQUEST['access_token'])) {
    header('HTTP/1.1 403 NO ACCESS');
    echo sprintf('no access with the token "%s"', var_export($_REQUEST['access_token'], true));
    exit;
}
if (isset($_POST['not_found'])) {
    header('HTTP/1.1 404 NOT FOUND');
}
if ($urlMatches['repoName'] === 'with-internal-server-error') {
    header('HTTP/1.1 500 INTERNEL SERVER ERROR');
    echo "error";
    exit;
}


echo json_encode($response);

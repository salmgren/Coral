<?php

interface ILSClient {
    function addVendor($vendor);
    function getVendor($id);
    function getVendorByName($name);
    function getVendorByExactName($name);
    function vendorExists($name);
    function getILSName();
    function getILSURL();
    function getVendorURL();
}

?>

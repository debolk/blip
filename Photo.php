<?php

require_once('BlipResource.php');

/**
 * @uri /persons/:uid/photo/:width/:height
 */
class Photo extends BlipResource
{
    /**
     * @method GET
     * @loggedIn bekend
     * @return string
     */
    public function index($uid, $width, $height)
    {
        // Cast parameters
        $width = (int)$width;
        $height = (int)$height;

        // Find the user
        $data = Models\LdapPerson::fromUid($uid);
        if (!$data) {
            return new Tonic\Response(404, "The specified uid was not found");
        }

        // Try to read the picture from LDAP
        $photo = null;
        if (isset($data->jpegphoto)) {
            $photo = $data->jpegphoto;
        }

        // Try to read a picture from gravatar
        if (!$photo) {
            $hash = md5(strtolower(trim($data->email)));
            $url = "http://www.gravatar.com/avatar/$hash?s=$width&rating=g&d=404";
            $request = curl_init($url);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($request);

            if (curl_getinfo($request, CURLINFO_HTTP_CODE) == '200') {
                $photo = $result;
            }
        }

        // Retrieve an image from placekitten.com
        if (!$photo) {
            // Random per-user seed to generate different kittens
            $seed = ((int)substr(base_convert(md5($uid), 16, 10), -6)) % 500;

            $request = curl_init('http://placekitten.com/g/' . (1024 + $seed) . '/' . (1024 + $seed));
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            $photo = curl_exec($request);
        }

        // Process picture to display
        $i = new Imagick();
        $i->readImageBlob($photo);
        $i->setImageInterpolateMethod(Imagick::INTERPOLATE_BICUBIC);

        // Scale to best fit
        if ($i->getImageWidth() > $i->getImageHeight()) {
            $i->resizeImage($width, 0, Imagick::FILTER_CATROM, 1);
        } else {
            $i->resizeImage(0, $height, Imagick::FILTER_CATROM, 1);
        }
        $i->setImageFormat('jpg');

        // Return the completed image
        $result = new Tonic\Response(200, $i);
        $result->ContentType = "image/jpeg";
        return $result;
    }
}

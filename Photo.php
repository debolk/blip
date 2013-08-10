<?php

require_once('BlipResource.php');

/**
 * @uri /persons/:uid/photo/:width/:height
 */
class Photo extends BlipResource
{
  /**
   * @method GET
   * @loggedIn lid
   * @return string
	 * @cache 86400
   */
  public function index($uid, $width, $height)
  {
    $width = (int)$width;
    $height = (int)$height;

    $data = Models\LdapPerson::fromUid($uid);
    if(!$data)
      return new Tonic\Response(404, "The specified uid was not found");

    if(!isset($data->jpegphoto))
    {
			$nr = base_convert(md5($uid), 16, 10);
			$nr = substr($nr, -6);
			$nr = (int)$nr;
			$off = $nr % 500;

      $c = curl_init('http://placekitten.com/g/' . (1024 + $off) . '/' . (1024 + $off));
      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

      $photo = curl_exec($c);
    } else {
      $photo = $data->jpegphoto;
    }

    $i = new Imagick ();
    $i->readImageBlob($photo);
    $i->setImageInterpolateMethod(Imagick::INTERPOLATE_BICUBIC);

    //Scale to best fit
    if($i->getImageWidth() > $i->getImageHeight())
      $i->resizeImage($width, 0, Imagick::FILTER_CATROM, 1);
    else
      $i->resizeImage(0, $height, Imagick::FILTER_CATROM, 1);

    $i->setImageFormat('jpg');
    
    $result = new Tonic\Response(200, $i);
    $result->ContentType = "image/jpeg";

    return $result;
  }
}

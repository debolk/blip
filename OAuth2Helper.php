<?php
class OAuth2Helper {
    /**
     * Returns false if the user is Authorized or returns a Tonic Response stating the error
     */
    public static function IsUnauthorized($resource)
    {
        if(isset($_POST['access_token']))
            $access_token = $_POST['access_token'];
        elseif(isset($_GET['access_token']))
            $access_token = $_GET['access_token'];
        else
            return new Tonic\Response(401, '{"error":"invalid_token","error_description":"No access token was provided"}');

        $base = getenv('OAUTH2_RESOURCE');
        $path = $base . $resource . '?access_token=' . urlencode($access_token);

        $c = curl_init($path);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $body = curl_exec($c);

        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);

        if($code == 200)
            return false;

        return new Tonic\Response($code, $body);
    }
}

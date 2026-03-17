<?PHP
//Set our script to JSON
header( 'Content-Type: application/json' );
 
//Web Login Page
$ServerURL                =    "vantrap.3cx.co.ee";  //e.g.  "company.3cx.co.uk"
 
//Web URL Credentials

$LoginCreds                =    new stdClass( );
$LoginCreds->username = "vantrap19@gmail.com";  //admin
$LoginCreds->password = "GUC5t6ZX8p";  //Password
 
//Function to post data to website and read returned cookie
function Get3CXCookie( )
{
    //Define our variables are globals
    global $ServerURL, $LoginCreds;
    
    //Encode Logon into JSON Data for body
    $UserDetails    =    json_encode( $LoginCreds );
 
    //Create our POST login with headers, ensure close!
    $PostData        =    file_get_contents( "https://". $ServerURL ."/api/login", null, stream_context_create( array(
        'http' => array(
                   'protocol_version'    =>    '1.1',
                'user_agent'        =>    'PHP',
                'method'            =>    'POST',
                'header'            =>    'Content-type: application/json\r\n'.
                                        'User-Agent: PHP\r\n'.
                                        'Connection: close\r\n' .
                                        'Content-length: ' . strlen( $UserDetails ) . '',
                'content'            =>    $UserDetails,
        ),
    ) ) );
    
    //Take response header 9 and break it into an array using explode from "; " which separates each variable
    $TempCookie        =    explode( "; ", $http_response_header[9] );
    
    //Build our required cookie
    $FinalCookie    =    substr( $TempCookie[0], 12 );
    
    //Return the cookie Data if auth succeeded
    if( $PostData == "AuthSuccess" )
        return $FinalCookie;
 
    //Return null/blank if auth failed
    return null;
}
 
//Function to GET data from API and read returned data
function GetAPIData( $API, $AuthCookie )
{
    //Define our variable is a global
    global $ServerURL;
 
    //Create our GET with headers
    $GetData        =    file_get_contents( "https://". $ServerURL ."/api/" . $API, null, stream_context_create( array(
        'http' => array(
               'protocol_version'    =>    '1.1',
             'user-agent'        =>    'PHP',
            'method'            =>    'GET',
            'header'            =>  'Cookie: '. $AuthCookie .''
            ),
    ) ) );
 
    //Return the API Data
    return $GetData;
}



?>


How the flow of dropbox auth works.

Ciniki does not use the built in auth flows for either javascript or PHP, it uses a combination
of the two. This is done to make it as seamless as possible for the user. Due to the method of
authentication and tokens in Ciniki, this is the best way current (mar 2015).

1. tenants/public/settingsAPIsGet.php generates the csrf and returns to UI.

1. UI creates cookies for api_key, auth_token, tnid, csrf

1. UI creates new window to dropbox requesting auth.

1. dropbox auth and redirectory to ciniki-apis.php (oauth2 processor for remote services)

1. ciniki-apis.php has access to cookie info, and parameters from dropbox auth in _GET.

1. ciniki-apis.php validates with dropbox the code and gets a token, which stores with tenant.

1. Done.

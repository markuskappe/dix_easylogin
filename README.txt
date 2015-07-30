***************************
*       INSTALLATION      *
***************************

Be sure you have cURL installed.
Create a facebook app (https://developers.facebook.com/apps) with your domain and write down the App ID and the App Secret
Create a twitter app (https://dev.twitter.com/apps/new) with your domain and write down the Consumer ID and the Consumer Secret
Create a Linkedin app (https://www.linkedin.com/secure/developer) with your domain and write down the API Key and the Secret Key. You will need to enter the verification URL (see FAQ).
Google changed from OpenID to OAuth2, so you should register a Google app as well (https://console.developers.google.com/ → APIS & AUTH) and write down the Client ID and the Client Secret. You will need to enter the verification URL (see FAQ). Note that due to compatibility you have to enable “google_oauth2” and disable “google” in TS constants.
1. Install extension
2. Insert a content element "login" on any page, it can be on a page not visible to the user (e.g. "hide in menu"). 
Write down uid of that record.
That login should work (that means a user storage page with a user record and usergroup record has been created and the page id of the user storage is configured. If you are not sure what that means, read this: http://wiki.typo3.org/Felogin )
3. Insert a content element "plugin" - "easylogin" on the login page

4. Include the static TypoScript (Include static (from extensions): Easylogin), insert these constants into your TS template and fit them to your needs:

plugin.tx_dixeasylogin_pi1 {
		# if jQuery, jQueryUI and the lightness theme should be included by default. 
		# if turned off, you have to take care for yourself that the libraries 
		# are loaded (smart if other extensions also include jQuery)
	include_jQuery = 1

		# if the user should be created when not already found in the database
	allowCreate = 1

		# create user only if email address matches this domain(s)
		# e.g. "example.com, *.mycompany.org"
	trustedDomains = *
	
		# if the user should be able to connect his login with a login provider 
		# when already authenticated
	allowUpdate = 1

		# where the fe_users records should be stored when created
	user_pid = 6
	
		# when a user is created, he will get this usergroup(s)
	usergroup = 1
	
		# page where the "easylogin" plugin is located
		# used for the xrds definition
	pid_loginPage = 21
	
		# uid of the common login
	uid_felogin = 10
	
		# register a facebook app to get these two values
	facebook_appID = YOUR-APP-ID
	facebook_appSecret = YOUR-APP-SECRET
	
		# register a twitter app to get these two values
	twitter_consumerKey = YOUR-CONSUMER-KEY
	twitter_consumerSecret = YOUR-CONSUMER-SECRET
	
		# register a xing app to get these two values
	xing_consumerKey = YOUR-CONSUMER-KEY
	xing_consumerSecret = YOUR-CONSUMER-SECRET

		# register a linkedin app to get these two values
	linkedin_consumerKey = YOUR-CONSUMER-KEY
	linkedin_consumerSecret = YOUR-CONSUMER-SECRET

		# register a google app to get these two values
	google_clientID = YOUR-CONSUMER-KEY
	google_clientSecret = YOUR-CONSUMER-SECRET

		# enable or disable login methods
	disable.felogin = 0
	disable.google = 1
	disable.yahoo = 0
	disable.myopenid = 0
	disable.wordpress = 0
	disable.facebook = 0
	disable.facebook_oauth2 = 1
	disable.twitter = 0
	disable.xing = 0
	disable.linkedin = 0
	disable.google_oauth2 = 0
}


See ext/dix_easylogin/static/setup.txt for further configuration
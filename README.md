# droopy
![drop files here](assets/front1.png)

droopy is a unique page where you simply drop and upload any file size, thanks to chunking. Based on Dropzone.js and php.

Default chunk size of 1000000b to bypass most enterprise proxies that would prevent more then 1Mb uploads.

## Features
version=1.1.0

- [x] fallback methods and command line ready with curl
- [x] chunking: because sometimes ou are behind a dumb IDS or firewall that will prevent you from uploading more then 1MB at a time
- [x] simplicity
- [x] works out of the box. No configuration needed
- [x] forbidden characters protection in filenames uploaded
- [x] everything goes to /droopy/uploads

## Installation

Clone the repo, `mkdir droopy/uploads` subfolder and other cutom subfolders if needed, serve it via nginx. Done.

## Word of wisdom

There is no security at all, this is PHP. You need at least basic authentication at the nginx level, or bad things will happen to you.

## nginx proxy sample

nginx proxy example below for [swag](https://docs.linuxserver.io/images/docker-swag):
```
  location ^~ /droopy {
    # enable the next two lines for http auth
    auth_basic "Restricted";
    auth_basic_user_file /config/nginx/.htpasswd;

    # enable the next two lines for ldap auth
    #auth_request /auth;
    #error_page 401 =200 /ldaplogin;

    # enable for Authelia
    #include /config/nginx/authelia-location.conf;

    include /config/nginx/proxy.conf;
    include /config/nginx/resolver.conf;

    set $upstream_app network-or-container;
    set $upstream_port 80;
    set $upstream_proto http;
    proxy_pass $upstream_proto://$upstream_app:$upstream_port;
  }
```

### TODO
- [ ] mkdir subfolders
- [ ] add some form options to the front such as chunk size, chunk on/off etc
- [ ] loads cutomizations off a json file
- [ ] show newly uploaded files directly under the dropzone
- [ ] ability to delete newly uploaded files
- [ ] create an nginx/php quirk to have dynamic and unique urls to share files
- [ ] ability to setup retention from the front and default retention
- [x] handle multiple custom subfolders based off a dictionary
- [x] add non-chunk, command line example
- [x] add non-chunk, native and basic 1-file upload form
- [x] handle filename without extension

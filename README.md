# droopy

droopy is a unique page where you simply drop and upload any file size, thanks to chunking. Based on Dropzone.js and php.
![drop files here](https://gitea.derewonko.com/audioscavenger/droopy/src/commit/c0f7a8261732922fadd8f00b726d828a1bb4c993/assets/screenshot1.png)

## Features

- [x] chunking: because sometimes ou are behind a dumb IDS or firewall that will prevent you from uploading more then 1MB at a time
- [x] simplicity
- [x] works out of the box. No configuration needed
- [x] forbidden characters protection in filenames uploaded
- [x] everything goes to /droopy/uploads

## Installation

Clone the repo, `mkdir droopy/uploads` subfolder, and serve it via nginx. Done.

## Word of wisdom

There is no security at all. This is PHP. You need at least basic authentication at the nginx level, or bad things will happen to you.

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
- [ ] show uploaded files directly under the dropzone
- [ ] create an nginx/php quirk to have dynamic and unique urls where files would be uploaded
- [ ] ability to delete files

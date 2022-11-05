# droopy

droopy is a unique page where you simply drop and upload any file size, thanks to chunking. Based on Dropzone.js and php.

Default chunk size of 1000000b to bypass most enterprise proxies that would prevent more then 1Mb uploads.

## TODO
- [ ] add some parameters on the front such as chunk size, chuk on/off etc
- [ ] loads cutomizations off a json file
- [x] handle multiple custom subfolders based off a dictionary
- [x] add non-chunk, native and basic 1-file upload form
- [x] handle filename without extension

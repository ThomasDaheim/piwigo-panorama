# piwigo-panorama
Piwigo plugin to show panoramas based on the pannellum js library.

There are two plugins available for Piwigo that show full 360-180 panoramas:

* PhotoSphere: http://piwigo.org/ext/extension_view.php?eid=794
* Panoramas: http://piwigo.org/ext/extension_view.php?eid=207

They both a) seem not to be maintained anymore and b) only support "full" 180/360 degree panoramas.

So this is an attempt to create a new plugin based on the pannellum js library (https://github.com/mpetroff/pannellum). As a basis the PhotoSphere plugin was used since I like the idea of storing in the database whether a picture should be shown as panorama. Otherwise the exif metadata (Google Photo Sphere XMP metadata) would need to be parsed, which doesn't sound like a good idea.

## Roadmap

- Initial creation of working plugin using manual selection of pictures (DONE)
- Add (optionally) algorithms to determine panoramas - e.g. google exif metadata, width/height ratio, ??? Could be used during picture upload to fill the flag initially

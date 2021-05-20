# Nashville Promise Zone Locator

[Try me!](https://aton.al/npz-locator)

## Description
This web app reports whether or not addresses are in the Nashville Promise Zone. It uses the [US Census Geocoder](https://geocoding.geo.census.gov/geocoder) to find the census tract of an address and compares the result with a list of subzones in the Nashville Promise Zone.

It is written in PHP and, aside from the census tract image files, exists wholly within the index.php file. The index.html file embeds a live Heroku page so the app can be accessed on a static site (i.e. [my GitHub Pages](https://aton.al/npz-locator)).

The app can take either a single address or a bulk upload.
## Examples
### Single Address
<img src='single.png' width='450px'/>

### Bulk Upload
<img src='bulk.png' width='650px'/>

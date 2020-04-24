# Sphinx Search

Sphinx Search is a package for Laravel 5 which queries Sphinxsearch and integrates with Eloquent.


## Installation

Add `MeridianStudio/sphinxsearch` to `composer.json`.

    "MeridianStudio/sphinxsearch": "dev-master"

Run `composer update` to pull down the latest version of Sphinx Search.

Now open up `app/config/app.php` and add the service provider to your `providers` array.
```php
'providers' => array(
	MeridianStudio\SphinxSearch\SphinxSearchServiceProvider::class,
)
```
## Configuration

To use Sphinx Search, you need to configure your indexes and what model it should query. To do so, publish the configuration into your app.

```php
php artisan vendor:publish
```

This will create the file `app/config/sphinxsearch.php`. Modify as needed the host and port, and configure the indexes, binding them to a table and id column.

```php
return array (
	'host'    => '127.0.0.1',
	'port'    => 9312,
	'indexes' => array (
		'my_index_name' => array ( 'table' => 'my_keywords_table', 'column' => 'id' ),
	)
);
```
Or disable the model querying to just get a list of result id's.
```php
return array (
	'host'    => '127.0.0.1',
	'port'    => 9312,
	'indexes' => array (
		'my_index_name' => FALSE,
	)
);
```

## Usage

You can add this line to the files, where you may use SphinxSearch:
```php
use MeridianStudio\SphinxSearch\SphinxSearch;
```

Basic query (raw sphinx results)
```php
$sphinx = new SphinxSearch();
$results = $sphinx->search('my query')->query();
```

Basic query (with Eloquent)
```php
$sphinx = new SphinxSearch();
$results = $sphinx->search('my query')->get();
```

Query another Sphinx index with limit and filters.
```php
$sphinx = new SphinxSearch();
$results = $sphinx->search('my query', 'index_name')
	->limit(30)
	->filter('attribute', array(1, 2))
	->range('int_attribute', 1, 10)
	->get();
```

Query with match and sort type specified.
```php
$sphinx = new SphinxSearch();
$result = $sphinx->search('my query', 'index_name')
	->setFieldWeights(
		array(
			'partno'  => 10,
			'name'    => 8,
			'details' => 1
		)
	)
	->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_EXTENDED)
	->setSortMode(\Sphinx\SphinxClient::SPH_SORT_EXTENDED, "@weight DESC")
	->get(true);  //passing true causes get() to respect returned sort order
```
Query and sort with geo-distant searching.
```php
$radius = 1000; //in meters
$latitude = deg2rad(25.99);
$longitude = deg2ra
$sphinx = new SphinxSearch();d(-80.35);
$result = $sphinx->search('my_query', 'index_name')
	->setSortMode(\Sphinx\SphinxClient::SPH_SORT_EXTENDED, '@geodist ASC')
	->setFilterFloatRange('@geodist', 0.0, $radius)
	->setGeoAnchor('lat', 'lng', $latitude, $longitude)
	->get(true);
```
## Integration with Eloquent

$sphinx = new SphinxSearch();
This package integrates well with Eloquent. You can change index configuration with `modelname` to get Eloquent's Collection (Illuminate\Database\Eloquent\Collection) as a result of `$sphinx->search`.
```php
return array (
	'host'    => '127.0.0.1',
	'port'    => 9312,
	'indexes' => array (
		'my_index_name' => array ( 'table' => 'my_keywords_table', 'column' => 'id', 'modelname' => 'Keyword' ),
	)
);
```

Eager loading with Eloquent is the same an one would expect:
```php
$sphinx = new SphinxSearch();
$results = $sphinx->search('monkeys')->with('arms', 'legs', 'otherLimbs')->get();
```
More on eager loading: http://laravel.com/docs/eloquent#eager-loading

## Paging results in Laravel 5 (with caching)

```php
Route::get('/search', function ()
{
    $page = Input::get('page', 1);
    $search = Input::get('q', 'search string');
    $perPage = 15;  //number of results per page
    // use a cache so you dont have to keep querying sphinx for every page!
    $results = Cache::remember(Str::slug($search), 10, function () use($search)
    {
    $sphinx = new SphinxSearch();
        return $sphinx->search($search)
        ->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_EXTENDED2)
        ->get();
    });
    if ($results) {
    	$totalItems = $results->count();
        $pages = array_chunk($results->all(), $perPage);

        $paginator = Paginator::make($pages[$page - 1], $totalItems, $perPage);
        return View::make('searchpage')->with('data', $paginator);
    }
    return View::make('notfound');
});
```
## Paging results in Laravel 4 (without caching)

```php
Route::get('/search', function ()
{
    $page = Input::get('page', 1);
    $search = Input::get('q', 'search string');
    $perPage = 15;  //number of results per page
    $items = null;

$sphinx = new SphinxSearch();
    $results = $sphinx->search($search)
        ->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_EXTENDED2)
        ->limit($perPage, ($page-1)* $perPage)
        ->get();

    if (!empty($results['total'])) {
        $items = Item::whereIn('id', array_keys($results['matches']))->get();
        $items = Paginator::make($items->all(), $results['total'], $perPage);

        $items->appends(['search' => $search]); //add search query string
    }

$sphinx = new SphinxSearch();
    if($error = $sphinx->getErrorMessage())
    {
        //
    }
});
```
And, in your view after you finish displaying rows,
```php
<?php echo $data->links()?>
```

## Searching through multiple Sphinx indexes (main/delta)

It is a common strategy to utilize the main+delta scheme (www.sphinxconsultant.com/sphinx-search-delta-indexing/). When using deltas, it is often necessary to query on multiple indexes simultaneously. In order to achieve this using SphinxSearch, modify your config file to include the "name" and "mapping" keys like so:

```php
return array (
	'host'    => '127.0.0.1',
	'port'    => 9312,
	'indexes' => array (
	    'name'    => array ('main', 'delta'),
	    'mapping' => array ( 'table' => 'properties', 'column' => 'id' ),
	)
);
```

You can also pass in multiple indexes (separated by comma or space) to your search like so (if the "mapping" key is not specified in the config, search retrieves ids):

```php
$sphinx = new SphinxSearch();
$sphinx->search('lorem', 'main, delta')->get();
```


## Retrieve search result excerpts using Sphinx

It is nifty to display excerpts with keywords highlighted in search result. Sphinx supports this feature natively. http://sphinxsearch.com/docs/archives/2.0.3/api-func-buildexcerpts.html

```php
$sphinx = new SphinxSearch();
$search = $sphinx->search($term, 'articles');
$articles = $search->get();
$excerpt = $search->excerpt(current($articles)->content);

or

$sphinx = new SphinxSearch();
$search = $sphinx->search($term, 'articles');
dd($search->excerpts(array_pluck($articles, 'content')));
```

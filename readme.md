## SphinxQL library for Laravel 4

This is a simple library that will help you to query a sphinx search server using SphinxQL.  
My main motivation for putting together this package was to interface easily 
with Sphinx Real-time indexes on Laravel 4 (Updating rt indexes is ONLY possible using SpinxQL)

As an added bonus, note that SphinxQL is much more performant than SphinxAPI: 
http://sphinxsearch.com/blog/2010/04/25/sphinxapi-vs-sphinxql-benchmark/

## Installation

Add `mnshankar/sphinxql` to `composer.json`.
```json
    "mnshankar/sphinxql": "1.0"
```    
Run `composer update` to pull down the latest version of Sphinxql. Note that Sphinxql has a 
dependency on 'FoolCode/SphinxQL-Query-Builder', which does much of the weight lifting 
(http://foolcode.github.io/SphinxQL-Query-Builder/)

Now open up `app/config/app.php` and add the service provider to your `providers` array.
```php
    'providers' => array(
        'mnshankar\Sphinxql\SphinxqlServiceProvider',
    )
```
and the alias:
```php
    'aliases' => array(
        'SphinxQL'         => 'mnshankar\Sphinxql\Facades\SphinxqlFacade',
    )
```

If you need to override the default configuration options, please use the config publish command

```php
php artisan config:publish mnshankar/sphinxql
``` 

## RT Indexes (real-time indexes) in Sphinx - a very short primer

The main differences between the conventional and RT indexes in Sphinx are:

1. RT uses a "push" model. This means that The task of keeping the index in sync with your database is 
delegated to your application (remember there is no "indexer" in an RT scheme). So, typically when using 
an RT index, the index starts out empty and gets populated over time using SphinxQL queries sent by your application. 

2. RT uses more RAM than the conventional indexer approach. Here is an informative blog detailing why:
http://www.ivinco.com/blog/sphinx-in-action-good-and-bad-in-sphinx-real-time-indexes/
Also, for more information, be sure to read the internals of RT at :
http://sphinxsearch.com/docs/archives/1.10/rt-internals.html

In either type of indexing (conventional or RT), for best results, you are recommended to 
dedicate *as much RAM* as is possible to your sphinx server. So, the additional 
RAM requirement should not deter you from using RT.

3. RT indexes are much simpler to setup. There is no need for messing with
cron jobs as the "indexer" component is not used. No main-delta schemes to worry about (typically). And, ofcourse
your index is live with search data instantly!

The more recent versions of Sphinx (2.1.1+) have made enormous strides in 
making RT indexes production ready:
http://sphinxsearch.com/blog/2013/01/15/realtime-index-improvements-in-2-1-1/

The current version uses sensible configuration defaults.. so you can have a 
clean sphinx.conf file that takes care of the most common scenarios out of the box.
http://sphinxsearch.com/docs/current.html#sphinx-deprecations-defaults

Here is the (minimal) sphinx.conf file that I use (for rt indexing):
```php
index rt_test
{
    type = rt   
    path = /var/lib/sphinxsearch/data/rt
    rt_field = title
    rt_field = content
    rt_attr_uint = gid
}
searchd
{
    # Configure the searchd listening port.
    listen = 9306:mysql41
    binlog_path = /var/lib/sphinxsearch/data
    pid_file = /var/www/sphinx-rt/app/storage/sphinx/searchd.pid

    # sudo searchd -c sphinx.conf - to start search daemon listening on above port   
    # mysql -P 9306 -h 127.0.0.1 - connect to sphinx server daemon
}        
```

## Query Builder Documentation

The SphinxQL query builder package for PHP developed by the kind folks at 
foolcode is VERY well documented and tested. I strongly recommend you go 
through their webpage at : http://foolcode.github.io/SphinxQL-Query-Builder/

## Misc Usage Tips

Using Laravel 4 model events (http://four.laravel.com/docs/eloquent#model-events), 
it is trivial to ensure that your model stays in sync with your index. 
Consider the following snippets that can be inserted into
"created", "updated" and "deleted" events for your model:

```php
Blog::created(function($model){
	$qins = SphinxQL::query()->insert()->into('rt_test');
	$qins->set(array('id'=>99, 'title'=>'My Title', 'content'=>'My Content', 'gid'=>444))->execute();
});
```
Similarly, Replace and delete can be easily handled like so:

```php
Blog::updated(function($model){
	$qrepl = SphinxQL::query()->replace()->into('rt_test');
	$qrepl->set(array('id'=>99, 'title'=>'My Title', 'content'=>'My Content', 'gid'=>444))->execute();
});
```
```php
Blog::deleted(function($model){
	SphinxQL::query()->delete()->from('rt_test')->where('id',99)->execute();
});
```
A search query can be constructed as:
```php
$q = SphinxQL::query()->select()->from('rt_test')->match('content', 'test');
	       ->execute();	       
```
The above statement returns an array of hits (if found).
View the generated sql statement:
```php
dd($q->compile()->getCompiled());
```

Please refer to the documentation
(http://foolcode.github.io/SphinxQL-Query-Builder/) for all available options.

Get the Meta info:
```php
SphinxQL::query()->meta();
```
It is also possible to run a raw sql query against the server like so:
```php
$q = SphinxQL::raw('select * from rt_test');
```
You can pass in any valid SphinxQL statement as a parameter to the raw function. 

## Integration with Eloquent

This package makes it really easy to integrate the results of a search with real
database rows. Remember that a sphinx query only returns a hit array containing ID's. 
It is upto the application to issue queries against the database to retrieve the actual table rows.

```php
$q = SphinxQL::query()->select()
			->from('rt_test')
			->match('content', 'test')
	       	->execute();
	       	
dd(Sphinx::with($q)->get('Blog'));	       	
```
The first statement runs the query against the sphinx server and returns an array.

The "with()" function takes the "hit" array returned by the Sphinx search engine and chains it to the "get"
The "get()" function has the following signature:
```php
public function get($name=null, $key='id')
``` 
where $name is either:

1. null - The function simply returns an array of ID's

2. An eloquent model - The function returns an EloquentCollection containing table rows matching the ids

3. A string representing a table name - The function returns an array of rows from the table specified (using DB::table('name'))

The $key parameter can be used to change the primary key column name (defaults to 'id')

### License

This software licensed under the [MIT license](http://opensource.org/licenses/MIT)
<?php

/**
 * Doctrine_Template_Solr tests.
 */
include dirname(__FILE__).'/../../../bootstrap/bootstrap.php';

$t = new lime_test(23);

// We need access to Solr to run our tests. Ensure it is running
if(!Doctrine::getTable('Post')->isSearchAvailable())
{
  $t->error('Solr is unavailable, cannot run tests');
  return 1;
}

// Ensure we're working on a clean index
Doctrine::getTable('Post')->deleteIndex();
Doctrine::loadData(dirname(__FILE__).'/../../../fixtures/project/data/fixtures');
$results = Doctrine::getTable('Post')->search('*:*');
$numResults = $results->numFound;
$post = Doctrine_Core::getTable('Post')
  ->createQuery('p')
  ->fetchOne();

$t->comment('-> Template availability');
$t->ok(is_callable(array(Doctrine::getTable('Post'), 'isSearchAvailable')),
  'Templates function are available');

$t->comment('-> getSolrId');
$t->is($post->getSolrId(), sprintf('Post_%d', $post->getId()),
  '::getSolrId() generates a correct identifier');

$t->comment('-> deleteFromIndex');
$post->deleteFromIndex();
$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, $numResults - 1,
  '::deleteFromIndex() correctly removes object from solr');

$t->comment('-> addToIndex');
$post->addToIndex();
$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, $numResults,
  '::addToIndex() correctly adds object to solr');

$t->comment('-> deleteIndex');
Doctrine::getTable('Post')->deleteIndex();
$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, 0,
  '::deleteIndex() leaves an empty index');

$t->comment('-> search');

$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, 0,
  '::search() returns correct result number when empty index');

$results = Doctrine::getTable('Post')->search('azerty');
$t->is($results->numFound, 0,
  '::search() returns no answer for a random unexisting word');

$post = new Post();
$post->title = 'azerty';
$post->body = 'this is my body';
$post->Thread = new Thread();
$post->Thread->title = 'test thread';
$post->save();

$otherPost = new Post();
$otherPost->title = 'foobar';
$otherPost->body = 'This is an azerty body';
$otherPost->Thread = $post->Thread;
$otherPost->save();

$results = Doctrine::getTable('Post')->search('azerty');
$t->is($results->numFound, 2,
  '::search() words are found in every fields');
$t->is($results->docs[0]->sf_meta_id, $post->getId(),
  '::search() order correct');

$post->title = 'blablabla';
$post->body = 'azerty';
$post->save();
$otherPost->title = 'azerty';
$otherPost->body = 'blablabla';
$otherPost->save();

/*$results = Doctrine::getTable('Post')->search('azerty');
$t->is($results->docs[0]->sf_meta_id, $otherPost->getId(),
  '::search() boost is taken into account');
 */

$t->comment('-> Creating tests objects. Please be patient');
Doctrine_Core::getTable('Post')->beginTransaction();
for($i = 0 ; $i < 20 ; $i++)
{
  $post = new Post();
  $post->title = "tototututata $i";
  $post->body = '';
  $post->Thread = $otherPost->Thread;
  $post->save();
}
Doctrine_Core::getTable('Post')->commit();
$results = Doctrine::getTable('Post')->search('tototututata', 3, 13);
$t->is($results->numFound, 20,
  '::search() "numFound" is correct even with "limit" set');
$t->is($results->start, 3,
  '::search() "start" has the correct value');
$t->is(count($results->docs), 13,
  '::search() the "limit" parameter is taken into account');

$t->comment('-> createSearchQuery');
$q = Doctrine::getTable('Post')->createSearchQuery('azerty');
$t->ok($q instanceof Doctrine_Query,
  '::createSearchQuery() returns a "Doctrine_Query" object');
$t->is($q->count(), 2,
  '::createSearchQuery() seems to return a valid query');

$q = Doctrine::getTable('Post')->createSearchQuery('tepébotiujdevauieauie');
$t->is($q->count(), 0,
  '::createSearchQuery() no results when stupid query');

$t->comment('-> transactions');
try
{
  Doctrine_Core::getTable('Post')->commit();
  $t->fail('::commit() raise an exception when not in a transaction');
}
catch(sfException $e)
{
  $t->pass('::commit() raise an exception when not in a transaction');
}

$t->is(Doctrine_Core::getTable('Post')->inTransaction(), false,
  '::inTransaction() returns false when no transaction is started');
Doctrine_Core::getTable('Post')->beginTransaction();
$t->is(Doctrine_Core::getTable('Post')->inTransaction(), true,
  '::inTransaction() returns true when a transaction is not yet commited');
Doctrine_Core::getTable('Post')->commit();
$t->is(Doctrine_Core::getTable('Post')->inTransaction(), false,
  '::inTransaction() returns false when the transaction is commited');


Doctrine_Core::getTable('Post')->beginTransaction();
$post->title = 'glopgloppasglop';
$post->save();
$results = Doctrine::getTable('Post')->search('glopgloppasglop');
$t->is($results->numFound, 0,
  '::commit() no index modification when uncommited yet');

Doctrine_Core::getTable('Post')->commit();
$results = Doctrine::getTable('Post')->search('glopgloppasglop');
$t->is($results->numFound, 1,
  '::commit() index is modified after commit');

Doctrine_Core::getTable('Post')->beginTransaction();
Doctrine_Core::getTable('Post')->beginTransaction();

$post->title = 'nothing';
$post->save();
Doctrine_Core::getTable('Post')->commit();

$results = Doctrine::getTable('Post')->search('glopgloppasglop');
$t->is($results->numFound, 1,
  '::commit() transactions can be nested');

Doctrine_Core::getTable('Post')->commit();
$results = Doctrine::getTable('Post')->search('glopgloppasglop');
$t->is($results->numFound, 0,
  '::commit() only when all transactions ended');

<?php

/**
 * BasePost
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $thread_id
 * @property string $title
 * @property clob $body
 * @property Thread $Thread
 * 
 * @method integer getThreadId()  Returns the current record's "thread_id" value
 * @method string  getTitle()     Returns the current record's "title" value
 * @method clob    getBody()      Returns the current record's "body" value
 * @method Thread  getThread()    Returns the current record's "Thread" value
 * @method Post    setThreadId()  Sets the current record's "thread_id" value
 * @method Post    setTitle()     Sets the current record's "title" value
 * @method Post    setBody()      Sets the current record's "body" value
 * @method Post    setThread()    Sets the current record's "Thread" value
 * 
 * @package    ##PROJECT_NAME##
 * @subpackage model
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
abstract class BasePost extends sfDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('post');
        $this->hasColumn('thread_id', 'integer', null, array(
             'type' => 'integer',
             'notnull' => true,
             ));
        $this->hasColumn('title', 'string', 255, array(
             'type' => 'string',
             'notnull' => true,
             'length' => '255',
             ));
        $this->hasColumn('body', 'clob', null, array(
             'type' => 'clob',
             'notnull' => true,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('Thread', array(
             'local' => 'thread_id',
             'foreign' => 'id',
             'onDelete' => 'CASCADE'));

        $solr0 = new Doctrine_Template_Solr(array(
             'fields' => 
             array(
              0 => 'title',
              1 => 'body',
             ),
             ));
        $this->actAs($solr0);
    }
}
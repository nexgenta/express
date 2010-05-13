<?php

/* Copyright (c) 2010 Mo McRoberts.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The names of the author(s) of this software may not be used to endorse
 *    or promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL
 * AUTHORS OF THIS SOFTWARE BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING 
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

uses('model');

class WordPress extends Model
{
	protected $blogId;
	protected $options;

	protected $db_terms;
	protected $db_term_taxonomy;
	protected $db_term_relationships;
	protected $db_options;
	protected $db_users;
	protected $db_posts;
	protected $db_postmeta;
		
	public static function getInstance($args = null, $className = null, $defaultDbIri = null)
	{
		return parent::getInstance($args, ($className === null ? 'WordPress' : $className), ($defaultDbIri === null ? WORDPRESS_IRI : $defaultDbIri));
	}
	
	public function __construct($args)
	{
		parent::__construct($args);
		if(isset($args['blogId']))
		{
			$this->blogId = $args['blogId'];
		}
		else if(defined('WPMU_BLOG_ID'))
		{
			$this->blogId = WPMU_BLOG_ID;
		}
		$this->updateTables();
	}
	
	protected function updateTables()
	{
		if(strlen($this->blogId))
		{
			$prefix = $this->blogId . '_';
		}
		else
		{
			$prefix = '';
		}
		$this->db_terms = $prefix . 'terms';
		$this->db_term_taxonomy = $prefix . 'term_taxonomy';
		$this->db_term_relationships = $prefix . 'term_relationships';
		$this->db_options = $prefix . 'options';
		$this->db_posts = $prefix . 'posts';
		$this->db_postmeta = $prefix . 'postmeta';
		$this->db_users = 'users';	
	}
	
	public function blogId()
	{
		return $this->blogId;
	}
	
	public function setBlogId($newId)
	{
		$this->blogId = $newId;
		$this->updateTables();
	}
	
	public function options()
	{
		if($this->options === null)
		{
			$this->options = array();
			$opts = $this->db->rows('SELECT * FROM {' . $this->db_options . '} WHERE "autoload" = ?', 'yes');
			foreach($opts as $opt)
			{
				$this->options[$opt['option_name']] = $opt['option_value'];
			}
		}
		return $this->options;
	}
	
	public function optionWithName($name)
	{
		$this->options();
		if(isset($this->options[$name]))
		{
			return $this->options[$name];
		}
		$this->options[$name] = $this->db->value('SELECT "option_value" FROM {' . $this->db_options . '} WHERE "option_name" = ?', $name);
		return $this->options[$name];
	}
	
	public function terms($taxonomy = null, $parent = null)
	{
		$args = array();
		$q = 'SELECT "tt".*, "t".* FROM {' . $this->db_terms . '} "t", {' . $this->db_term_taxonomy . '} "tt" WHERE "tt"."term_id" = "t"."term_id"';
		if($taxonomy !== null)
		{
			$q .= ' AND "tt"."taxonomy" = ?';
			$args[] = $taxonomy;
		}
		if($parent !== null)
		{
			$q .= ' AND "tt"."parent" = ?';
			$args[] = $parent;
		}
		return $this->db->rowsArray($q, $args);
	}
	
	public function termWithSlug($slug)
	{
		return $this->db->row('SELECT "tt".*, "t".* FROM {' . $this->db_terms . '} "t", {' . $this->db_term_taxonomy . '} "tt" WHERE "tt"."term_id" = "t"."term_id" AND "t"."slug" = ?', $slug);
	}

	public function termIdWithSlug($slug)
	{
		return $this->db->value('SELECT "t"."term_id" FROM {' . $this->db_terms . '} "t", {' . $this->db_term_taxonomy . '} "tt" WHERE "tt"."term_id" = "t"."term_id" AND "t"."slug" = ?', $slug);
	}
	
	public function postWithId($postId, $type = 'post', $status = 'publish')
	{
		if(null === ($post = $this->db->row('SELECT * FROM {' . $this->db_posts . '} WHERE "ID" = ? AND "post_type" = ? AND "post_status" = ?', $postId, $type, $status)))
		{
			return null;
		}
		$tax = $this->db->rows('SELECT "tt".*, "tr".*, "t".* FROM {' . $this->db_term_relationships . '} "tr", {' . $this->db_term_taxonomy . '} "tt", {' . $this->db_terms . '} "t" WHERE "tr"."object_id" = ? AND "tt"."term_taxonomy_id" = "tr"."term_taxonomy_id" AND "t"."term_id" = "tt"."term_id" ORDER BY "tr"."term_order" ASC', $post['ID']);
		foreach($tax as $t)
		{
			$post[$t['taxonomy']][] = $t;
		}
		$post['meta'] = array();
		$meta = $this->db->rows('SELECT "meta_key", "meta_value" FROM {' . $this->db_postmeta . '} WHERE "post_id" = ? ORDER BY "meta_id" ASC', $post['ID']);
		foreach($meta as $row)
		{
			$post['meta'][$row['meta_key']] = $row['meta_value'];
		}
		return $post;
	}
	
	public function postWithNameAndParent($postName, $parentId, $type = 'post', $status = 'publish')
	{
		if(null === ($post = $this->db->row('SELECT * FROM {' . $this->db_posts . '} WHERE "post_name" = ? AND "post_parent" = ? AND "post_type" = ? AND "post_status" = ?', $postName, intval($parentId), $type, $status)))
		{
			return null;
		}
		$tax = $this->db->rows('SELECT "tt".*, "tr".*, "t".* FROM {' . $this->db_term_relationships . '} "tr", {' . $this->db_term_taxonomy . '} "tt", {' . $this->db_terms . '} "t" WHERE "tr"."object_id" = ? AND "tt"."term_taxonomy_id" = "tr"."term_taxonomy_id" AND "t"."term_id" = "tt"."term_id" ORDER BY "tr"."term_order" ASC', $post['ID']);
		foreach($tax as $t)
		{
			$post[$t['taxonomy']][] = $t;
		}
		$post['meta'] = array();
		$meta = $this->db->rows('SELECT "meta_key", "meta_value" FROM {' . $this->db_postmeta . '} WHERE "post_id" = ? ORDER BY "meta_id" ASC', $post['ID']);
		foreach($meta as $row)
		{
			$post['meta'][$row['meta_key']] = $row['meta_value'];
		}
		return $post;		
	}
	
	public function latestPost($type = 'post', $termId = null)
	{
		$args = array();
		$q = 'SELECT "p".* FROM {' . $this->db_posts . '} "p"';
		if($termId !== null)
		{
			$q .= ', {' . $this->db_term_relationships . '} "tr", {' . $this->db_term_taxonomy . '} "tt"';
		}
		$q .= ' WHERE "p"."post_type" = ? AND "p"."post_status" = ?';
		$args[] = $type;
		$args[] = 'publish';
		if($termId !== null)		
		{
			$q .= ' AND "tr"."object_id" = "p"."ID" AND "tt"."term_taxonomy_id" = "tr"."term_taxonomy_id" AND "tt"."term_id" = ?';
			$args[] = $termId;
		}
		$q .= ' ORDER BY "p"."post_date_gmt" DESC';
		return $this->db->rowArray($q, $args);
	}

	public function latestPosts($type = 'post', $termId = null, $limit = 10, $offset = 0)
	{
		$args = array();
		$q = 'SELECT "p".* FROM {' . $this->db_posts . '} "p"';
		if($termId !== null)
		{
			$q .= ', {' . $this->db_term_relationships . '} "tr", {' . $this->db_term_taxonomy . '} "tt"';
		}
		$q .= ' WHERE "p"."post_type" = ? AND "p"."post_status" = ?';
		$args[] = $type;
		$args[] = 'publish';
		if($termId !== null)		
		{
			$q .= ' AND "tr"."object_id" = "p"."ID" AND "tt"."term_taxonomy_id" = "tr"."term_taxonomy_id" AND "tt"."term_id" = ?';
			$args[] = $termId;
		}
		$q .= ' ORDER BY "p"."post_date_gmt" DESC';
		if($limit)
		{
			$q .= ' LIMIT ' . intval($limit);
			if($offset)
			{
				$q .= ' OFFSET ' . intval($offset);
			}
		}
		return $this->db->rowsArray($q, $args);
	}
}

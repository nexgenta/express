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

require_once(dirname(__FILE__) . '/model.php');

class WordpressPage extends Page
{
	protected $modelClass = 'WordPress';
	protected $postType = 'index';
	protected $postId = null;
	protected $postList = false;
	protected $homepage = false;
	
	public function __construct($info = null)
	{
		parent::__construct();
		if(isset($info) && is_array($info))
		{
			$this->info = $info;
		}
	}
	
	public function process(Request $req)
	{
		if(isset($req->data['blogId']))
		{
			$this->model->setBlogId($req->data['blogId']);
		}
		$this->defaultSkin = $this->model->optionWithName('stylesheet');
		return parent::process($req);
	}
	
	protected function getObject()
	{
		$this->object = $this->request->data;
		$postsPage = $this->model->optionWithName('page_for_posts');
		if(isset($this->object['ID']))
		{
			$this->postId = $this->object['ID'];
		}
		if(isset($this->object['post_type']))
		{
			$this->postType = $this->object['post_type'];
		}
		if(isset($this->object['homepage']))
		{
			$this->homepage = true;
		}
		if(!empty($postsPage) && $postsPage == $this->postId)
		{
			$this->postList = true;
		}
		else if($this->homepage && (empty($postsPage) || empty($this->postId)))
		{
			$this->postList = true;
		}
		return true;
	}
	
	protected function assignTemplate()
	{
		parent::assignTemplate();
		$this->vars['postId'] = $this->postId;
		$this->vars['postList'] = $this->postList;
		$this->vars['homepage'] = $this->homepage;
		$this->vars['postType'] = $this->postType;
		if($this->homepage)
		{
			$this->vars['page_type'] .= ' home';
		}
		if($this->postId)
		{
			$this->vars['page_type'] .= ' ' . $this->postType . '-id-' . $this->postId;
		}
		if(isset($this->object['post_content']))
		{
			$this->vars['content'] = $this->object['post_content'];
		}
		$this->vars['site_title'] = $this->model->optionWithName('blogname');
		$this->vars['site_description'] = $this->model->optionWithName('blogdescription');
		if(isset($this->object['post_title']))
		{
			$this->vars['title'] = $this->object['post_title'];
		}
		else
		{
			$this->vars['title'] = null;
		}
		if(!strlen($this->vars['page_title']))
		{
			$this->vars['page_title'] = (strlen($this->vars['title']) ? $this->vars['title'] . ' • ' : '' ) . $this->vars['site_title'];
		}
	}
	
	protected function routePost(Request $req, $info, $defaultClass = 'WordpressPage', $pageType = null, $defaultTemplate = null)
	{
		$info['class'] = $defaultClass;
		if(isset($info['meta']['className'])) $info['class'] = $info['meta']['className'];
		if(isset($info['meta']['moduleName'])) $info['name'] = $info['meta']['moduleName'];
		if(isset($info['meta']['fileName'])) $info['file'] = $info['meta']['fileName'];
		if(!empty($info['meta']['fromRoot'])) $info['fromRoot'] = true;
		if(!empty($info['meta']['adjustBase'])) $info['adjustBase'] = true;
		if(isset($info['meta']['_wp_page_template'])) $info['templateName'] = $info['meta']['_wp_page_template'];
		if(!isset($info['templateName']) || !strcmp($info['templateName'], 'default'))
		{
			if(strlen($defaultTemplate))
			{
				$info['templateName'] = $defaultTemplate;
			}
			else
			{
				$info['templateName'] = $info['post_type'] . '.phtml';
			}
		}
		$info['page_type'] = $pageType;
		$req->data = $info;
		$inst = $this->routeInstance($req, $info);
		$inst->process($req);
		return false;
	}

	protected function unmatched(Request $req)
	{
		if(null !== ($path = $req->consume()))
		{
			/* Look for a child page */
			$postId = null;
			if(isset($req->data['ID']))
			{
				$postId = $req->data['ID'];
			}
			if(($page = $this->model->postWithNameAndParent($path, $postId, 'page')))
			{
				return $this->routePost($req, $page, 'WordpressPage', 'page');
			}
			$postsPage = $this->model->optionWithName('page_for_posts');
			if((!empty($postsPage) && $postsPage == $postId) || (!empty($req->data['homepage']) && empty($postsPage)))
			{
				$params = $req->params;
				array_unshift($params, $path);
				/* XXX: We need to intelligently match the entire array */
				if(($page = $this->model->postWithNameAndParent($path, null, 'post')))
				{
					return $this->routePost($req, $page, 'WordpressPost', null);
				}
			}
			$this->request = $req;
			return $this->error(Error::OBJECT_NOT_FOUND);
		}
		return parent::unmatched($req);
	}
}

/* In WP’s database, a “page” is really a type of “post”. It makes more sense
 * in terms of the front-end implementation for it to be the other way around,
 * really.
 */

class WordpressPost extends WordpressPage
{
	protected function getObject()
	{
		if(!parent::getObject())
		{
			return false;
		}
		$this->objects = $this->model->latestPosts();
		return true;
	}

	protected function assignTemplate()
	{
		parent::assignTemplate();
		$this->vars['isSingle'] = true;
		$this->vars['page_type'] .= ' blog single';
	}
}

/* A list of posts: possibly the main blog post list, possibly a tag,
 * category or date archive.
 */

class WordpressPostIndex extends WordpressPost
{
	protected function assignTemplate()
	{
		parent::assignTemplate();
		$this->vars['page_type'] .= ' blog';
	}
}
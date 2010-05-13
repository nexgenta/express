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

require_once(dirname(__FILE__) . '/page.php');

/* This handles __DEFAULT__ in the routemap */

class WordpressDefaultPage extends WordpressPage
{
	public function process(Request $req)
	{
		if(!(count($req->params)))
		{
			$options = $this->model->options();
			$defType = null;
			$defTemplate = 'home.phtml';
			if($options['show_on_front'] == 'page')
			{
				$pageId = $options['page_on_front'];
				$page = $this->model->postWithId($options['page_on_front'], 'page');
				$defType = 'page';
				$defClass = 'WordpressPage';
			}
			else
			{
				$page = array();
				$defClass = 'WordpressPostIndex';
			}
			$page['homepage'] = true;
			return $this->routePost($req, $page, $defClass, $defType, $defTemplate);
		}
		return $this->unmatched($req);
	}
}
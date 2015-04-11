<?php
/**
 * BloggerCMS - Easiest Static Blog Generator
 *
 * @author      Sarfraz Ahmed <sarfraznawaz2005@gmail.com>
 * @copyright   2015 Sarfraz Ahmed
 * @link        https://bloggercms.github.io
 * @version     1.0.0
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
class Post
{
    const STATUS_PUBLISHED = 'publised';
    const STATUS_DRAFT = 'draft';
    const STATUS_TRASH = 'trashed';

    private $metaFile = 'data/posts.json';
    private $categoriesFile = 'data/categories.json';
    private $tagsFile = 'data/tags.json';

    public function getAdd()
    {
        global $app;

        $data['categories'] = MetaDataWriter::getFileData($this->categoriesFile);
        sort($data['categories']);
        $data['tags'] = MetaDataWriter::getFileData($this->tagsFile);
        sort($data['tags']);
        //pretty_print($data, 0);

        $app->render('addpost.php', array('title' => 'Add Post', 'data' => $data));
    }

    public function add()
    {
        global $app;
        $post = $app->request()->post();

        if (isset($post['addpost'])) {
            $this->addPost(self::STATUS_PUBLISHED);
        } else {
            $this->addPost(self::STATUS_DRAFT);
        }

    }

    private function addPost($status)
    {
        global $app;
        $post = $app->request()->post();

        // add post
        $post['dated'] = date($app->view()->getData('dateFormat'));
        $post['slug'] = getSlugName($post['title']);
        $post['status'] = $status;

        MetaDataWriter::updateFileData($this->metaFile, $post, true);

        // also write tags file
        $array[] = MetaDataWriter::getFileData($this->tagsFile);
        $array[] = $post['tags'];

        $array = arrayFlatten($array);
        $array = array_unique($array);

        MetaDataWriter::writeData($this->tagsFile, $array);

        $app->flash('info', 'Saved Successfully');
        $app->redirect($_SERVER['HTTP_REFERER']);
    }

    public function get()
    {
        global $app;

        $data = MetaDataWriter::getFileData($this->metaFile);
        //pretty_print($data, 0);

        // sort posts by latest first
        $dates = array();
        foreach ($data as $key => $value) {
            $dates[] = strtotime($value['dated']);
        }

        array_multisort($dates, SORT_DESC, SORT_NUMERIC, $data);

        $data['categories'] = MetaDataWriter::getFileData($this->categoriesFile);
        sort($data['categories']);

        $app->render('posts.php', array('title' => 'View Posts', 'data' => $data));
    }

    public function edit()
    {
        global $app;

        $data = MetaDataWriter::getFileData($this->metaFile);

        // sort posts by latest first
        $dates = array();
        foreach ($data as $key => $value) {
            $dates[] = strtotime($value['dated']);
        }

        array_multisort($dates, SORT_DESC, SORT_NUMERIC, $data);

        $params = $app->router()->getCurrentRoute()->getParams();
        $id = $params['param'];

        $data[$id]['categories'] = MetaDataWriter::getFileData($this->categoriesFile);
        sort($data['categories']);

        $allTags = MetaDataWriter::getFileData($this->tagsFile);
        $postTags = $data[$id]['tags'];

        $data[$id]['tagsAll'] = $allTags + $postTags;
        $data[$id]['tagsAll'] = array_unique($data[$id]['tagsAll']);
        sort($data[$id]['tagsAll']);

        //pretty_print($data[$id]);

        $app->render('editpost.php', array('title' => 'Edit Post', 'data' => $data[$id], 'id' => $id));
    }

    public function update()
    {
        global $app;

        $data = MetaDataWriter::getFileData($this->metaFile);
        $post = $app->request()->post();

        // sort posts by latest first
        $dates = array();
        foreach ($data as $key => $value) {
            $dates[] = strtotime($value['dated']);
        }

        array_multisort($dates, SORT_DESC, SORT_NUMERIC, $data);

        $params = $app->router()->getCurrentRoute()->getParams();
        $id = $params['param'];

        // update values
        foreach ($post as $key => $value) {
            $data[$id][$key] = $value;
        }

        $data[$id]['slug'] = getSlugName($post['title']);
        $data[$id]['generated'] = '';

        MetaDataWriter::writeData($this->metaFile, $data);

        // also write tags file
        $array[] = MetaDataWriter::getFileData($this->tagsFile);
        $array[] = $post['tags'];

        $array = arrayFlatten($array);
        $array = array_unique($array);

        MetaDataWriter::writeData($this->tagsFile, $array);

        $app->flash('info', 'Saved Successfully');
        $app->redirect($_SERVER['HTTP_REFERER']);
    }

    public function remove()
    {
        global $app;

        $params = $app->router()->getCurrentRoute()->getParams();
        $id = $params['param'];

        $data = MetaDataWriter::getFileData($this->metaFile);

        // sort posts by latest first
        $dates = array();
        foreach ($data as $key => $value) {
            $dates[] = strtotime($value['dated']);
        }

        array_multisort($dates, SORT_DESC, SORT_NUMERIC, $data);

        $data[$id]['status'] = self::STATUS_TRASH;

        MetaDataWriter::writeData($this->metaFile, $data);

        $app->flash('info', 'Deleted Successfully');
        $app->redirect($_SERVER['HTTP_REFERER']);
    }

    public function restore()
    {
        global $app;

        $params = $app->router()->getCurrentRoute()->getParams();
        $id = $params['param'];

        $data = MetaDataWriter::getFileData($this->metaFile);

        // sort posts by latest first
        $dates = array();
        foreach ($data as $key => $value) {
            $dates[] = strtotime($value['dated']);
        }

        array_multisort($dates, SORT_DESC, SORT_NUMERIC, $data);

        $data[$id]['status'] = self::STATUS_DRAFT;

        MetaDataWriter::writeData($this->metaFile, $data);

        $app->flash('info', 'Restored Successfully');
        $app->redirect($_SERVER['HTTP_REFERER']);
    }

    public function publish()
    {
        global $app;

        $params = $app->router()->getCurrentRoute()->getParams();
        $id = $params['param'];

        $data = MetaDataWriter::getFileData($this->metaFile);

        // sort posts by latest first
        $dates = array();
        foreach ($data as $key => $value) {
            $dates[] = strtotime($value['dated']);
        }

        array_multisort($dates, SORT_DESC, SORT_NUMERIC, $data);

        $data[$id]['dated'] = date($app->view()->getData('dateFormat'));
        $data[$id]['slug'] = getSlugName($data[$id]['title']);
        $data[$id]['status'] = self::STATUS_PUBLISHED;
        $data[$id]['generated'] = '';

        MetaDataWriter::writeData($this->metaFile, $data);

        $app->flash('info', 'Restored Successfully');
        $app->redirect($_SERVER['HTTP_REFERER']);
    }

    public function removeTrashed()
    {
        global $app;

        $params = $app->router()->getCurrentRoute()->getParams();
        $id = $params['param'];

        $data = MetaDataWriter::getFileData($this->metaFile);

        // sort posts by latest first
        $dates = array();
        foreach ($data as $key => $value) {
            $dates[] = strtotime($value['dated']);
        }

        array_multisort($dates, SORT_DESC, SORT_NUMERIC, $data);

        // remove post from disk
        $postPath = '../public/post/' . getSlugName($data[$id]['title']) . '.html';
        @unlink($postPath);

        unset($data[$id]);

        MetaDataWriter::writeData($this->metaFile, $data);

        $app->flash('info', 'Restored Successfully');
        $app->redirect($_SERVER['HTTP_REFERER']);
    }

    public function getTotalPostsCount()
    {
        $data = MetaDataWriter::getFileData($this->metaFile);
        return count($data);
    }

    public function getTotalPostsCountPublished()
    {
        $counter = 0;
        $data = MetaDataWriter::getFileData($this->metaFile);

        foreach ($data as $post) {
            if ($post['status'] === self::STATUS_PUBLISHED) {
                $counter ++;
            }
        }

        return $counter;
    }

    public function getTotalPostsCountDrafts()
    {
        $counter = 0;
        $data = MetaDataWriter::getFileData($this->metaFile);

        foreach ($data as $post) {
            if ($post['status'] === self::STATUS_DRAFT) {
                $counter ++;
            }
        }

        return $counter;
    }

}
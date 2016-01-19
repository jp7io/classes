<?php

class Jp7_WordPress_Blog extends Jp7_WordPress_RecordAbstract
{
    const PK = 'blog_id';

    public function getFirstPost($options = [])
    {
        return reset($this->getPosts(['limit' => 1] + $options));
    }

    public function getPosts($options = [])
    {
        if (!$this->blog_id) {
            throw new Exception('Field "blog_id" is empty.');
        }

        $options += [
            'from' => $this->getPrefix().'posts',
            'fields' => '*',
        ];

        if ($options['class']) {
            $class = $options['class'];
        } else {
            $class = 'Jp7_WordPress';
        }

        return self::retrieveObjects($this->_db, $options, $class.'_Post');
    }

    public function getOptionByName($name, $options = [])
    {
        $options['where'][] = "option_name = '".$name."'";

        return $this->getFirstOption($options);
    }

    public function getFirstOption($options = [])
    {
        return reset($this->getOptions(['limit' => 1] + $options));
    }

    public function getOptions($options = [])
    {
        if (!$this->blog_id) {
            throw new Exception('Field "blog_id" is empty.');
        }

        $options += [
            'from' => $this->getPrefix().'options',
            'fields' => '*',
        ];

        if ($options['class']) {
            $class = $options['class'];
        } else {
            $class = 'Jp7_WordPress';
        }

        return self::retrieveObjects($this->_db, $options, $class.'_Option');
    }

    public function getNome()
    {
        $option = $this->getOptionByName('blogname');

        return $option->option_value;
    }

    public function getUrl()
    {
        $option = $this->getOptionByName('siteurl');

        return $option->option_value;
    }

    public function getImagem()
    {
        $option = $this->getOptionByName('widget_text');

        $array = $option->option_value;

        if (is_array($array)) {
            foreach ($array as $item) {
                if ($content = $item['text']) {
                    if (stripos($content, '<img') !== false) {
                        $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
                        preg_match($imgsrc_regex, $content, $matches);
                        unset($imgsrc_regex);
                        unset($content);
                        if (is_array($matches) && !empty($matches)) {
                            if ($url = $matches[2]) {
                                return $url;
                            }
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }
        }
    }

    public function getPrefix()
    {
        return Jp7_WordPress::getPrefix().(($this->blog_id > 1) ? $this->blog_id.'_' : '');
    }

    public function getTopic($options = [])
    {
        $options += [
            'from' => Jp7_WordPress::getPrefix().'blogs_cets_topic',
            'fields' => '*',
            'where' => 'id = (SELECT topic_id FROM '.Jp7_WordPress::getPrefix().'blogs_cets_topic_relationship WHERE blog_id = '.$this->blog_id.')',
        ];

        if ($options['class']) {
            $class = $options['class'];
        } else {
            $class = 'Jp7_WordPress';
        }

        $topics = self::retrieveObjects($this->_db, $options, $class.'_Topic');

        return reset($topics);
    }
}

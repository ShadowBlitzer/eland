<?php

namespace form;

use service\thumbprint;
use exception\conflicting_options_exception;

class typeahead_type_attr
{
    private $thumbprint;

    public function __construct(thumbprint $thumbprint)
    {
        $this->thumbprint = $thumbprint;
    }

    public function get(array $options)
    {
        $attr = [];

        if (isset($options['source_id'])) 
        {
            if (isset($options['data_path']))
            {
                throw new conflicting_options_exception(sprintf(
                    'options "source_id" and "data_path" can 
                    not be both set in %s', __CLASS__));
            }

            $attr['data-typeahead-source-id'] = $options['source_id'];
        }
        else
        {
            if (isset($options['data_path']))
            {
                $paths = $options['data_path'];
                $paths = is_string($paths) ? [$paths] : $paths;
                $data_typeahead = [];

                foreach($paths as $p)
                {
                    $data_typeahead[] = [
                        'path'          => $p,
                        'thumbprint'    => $this->thumbprint->get($p)
                    ];
                }

                $attr['data-typeahead'] = json_encode($data_typeahead);
            }
        }

        if (isset($options['process']))
        {
            $attr['data-typeahead-process'] = $options['process'];
        }

        return $attr;
    }    
}
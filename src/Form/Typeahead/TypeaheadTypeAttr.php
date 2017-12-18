<?php

namespace App\Form\Typeahead;

use App\Service\Thumbprint;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use exception\conflicting_options_exception;
use exception\invalid_format_exception;
use exception\missing_options_exception;

class TypeaheadTypeAttr
{
    private $thumbprint;
    private $requestStack;
    private $schema;
    private $access;

    public function __construct(
        Thumbprint $thumbprint, 
        RequestStack $requestStack, 
        UrlGeneratorInterface $urlGenerator
    )
    {
        $this->thumbprint = $thumbprint;
        $request = $requestStack->getCurrentRequest();   
        $this->schema = $request->attributes->get('schema');
        $this->access = $request->attributes->get('access'); 
        $this->urlGenerator = $urlGenerator;
    }

    public function get(array $options)
    {
        $attr = [];

        if (isset($options['source_route']))
        {
            if (isset($options['source_id']))
            {
                throw new conflicting_options_exception(sprintf(
                    'options "source_route" and "source_id" can 
                    not be both set in %s', __CLASS__));
            }

            if (isset($options['source']))
            {
                throw new conflicting_options_exception(sprintf(
                    'options "source_route" and "source" can 
                    not be both set in %s', __CLASS__));
            }
            
            $source = ['route' => $options['source_route']];

            if (isset($options['source_params']))
            {
                if (!is_array($options['source_params']))
                {
                    throw new invalid_format_exception(sprintf(
                        'option "source_params" must be an
                        array in %s', __CLASS__));
                }

                $source['params'] = $options['source_params'];
            }

            $options['source'] = [$source];
        }

        if (isset($options['source_id'])) 
        {
            $attr['data-typeahead-source-id'] = $options['source_id'];
        }
        else if (isset($options['source']))
        {
            if (isset($options['data_path']))
            {
                throw new conflicting_options_exception(sprintf(
                    'options "data_path" and "data_source" can 
                    not be both set in %s', __CLASS__));
            }

            $source = $options['source'];

            if (!is_array($source))
            {
                throw new invalid_format_exception(sprintf(
                    'option "source" must be an
                    array in %s', __CLASS__));
            }

            $data_typeahead = [];
            $base_params = [
                'schema'    => $this->schema,
                'access'    => $this->access,
            ];
            
            foreach ($source as $s)
            {
                if (!isset($s['route']))
                {
                    throw new invalid_format_exception(sprintf(
                        'a "route" key is missing from option "source" 
                        in %s', __CLASS__));
                }

                $params = isset($s['params']) && is_array($s['params']) ? $s['params'] : [];
                $params = array_merge($params, $base_params);

                $path = $this->urlGenerator->generate($s['route'], $params);

                $data_typeahead[] = [
                    'path'          => $path,
                    'thumbprint'    => $this->thumbprint->get($path),
                ];
            }

            $attr['data-typeahead'] = json_encode($data_typeahead);            
        }
        else
        {
            throw new missing_options_exception(sprintf(
                'either "data-source" of "source" option needs 
                to be set for the typeahead type in %s', __CLASS__));
        }

        if (isset($options['process']))
        {
            $attr['data-typeahead-process'] = $options['process'];
        }

        return $attr;
    }    
}
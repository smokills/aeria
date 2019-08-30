<?php

namespace Aeria\Field\Fields;

use Aeria\Field\Fields\BaseField;
use Aeria\Field\FieldNodeFactory;
use Aeria\Field\Interfaces\FieldInterface;

class RepeaterField extends BaseField
{
    public $isMultipleField = true;

    public function get(array $metas, bool $skipFilter = false) {
      $stored_value = (int)parent::get($metas, true);
      $children = [];

      $fields = $this->config['fields'];

      for ($i = 0; $i < $stored_value; ++$i) {
        $child = new \StdClass();
        for ($j = 0; $j < count($fields); ++$j) {
          $field_config = $fields[$j];

          $child->{$field_config['id']} = FieldNodeFactory::make(
            $this->key, $field_config, $this->sections, $i
          )->get($metas);
        }

        if(count($fields) === 1){
          $children[] = $child->{$fields[0]['id']};
        }else{
          $children[] = $child;
        }
      }
      if(!$skipFilter)
        $children = apply_filters('aeria_get_repeater', $children, $this->config);
      return $children;
    }

    public function getAdmin(array $metas, array $errors) {
      $stored_value = (int)parent::get($metas, true);
      $children = [];

      $fields = $this->config['fields'];

      for ($i = 0; $i < $stored_value; ++$i) {
        $child = [];
        for ($j = 0; $j < count($fields); ++$j) {
          $field_config = $fields[$j];

          $child[] = FieldNodeFactory::make(
            $this->key, $field_config, $this->sections, $i
          )->getAdmin($metas, $errors);
        }
        $children[] = $child;
      }

      return array_merge(
        $this->config,
        [
          "value" => $stored_value,
          "children" => $children
        ]
      );
    }


    public function set($context_ID, $context_type, array $metas, array $newValues, $validator_service, $query_service) {
      $stored_values = (int)parent::set($context_ID, $context_type, $metas, $newValues, $validator_service, $query_service)["value"];
      if(!$stored_values) {
        return;
      }

      $fields = $this->config['fields'];

      for ($i = 0; $i < $stored_values; ++$i) {
        for ($j = 0; $j < count($fields); ++$j) {
          FieldNodeFactory::make(
            $this->key, $fields[$j], $this->sections, $i
          )->set($context_ID, $context_type, $metas, $newValues, $validator_service, $query_service);
        }
      }
      $this->deleteOrphanMeta($this->key,$metas, $newValues);
    }

    private function deleteOrphanMeta ($parentKey, $metas, $newValues)
    {
        $oldFields=static::pregGrepKeys("/^".$parentKey."/", $metas);
        $newFields=static::pregGrepKeys("/^".$parentKey."/", $newValues);
        $deletableFields = array_diff_key($oldFields, $newFields);
        foreach ($deletableFields as $deletableKey => $deletableField) {
            delete_post_meta($newValues['post_ID'], $deletableKey);
        }
    }

    private static function pregGrepKeys($pattern, $input) {
      return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input))));
    }
}

<?php
namespace Kit\GeneratorBundle\Tools;

use Doctrine\ORM\Tools\EntityGenerator as BaseEntityGenerator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\DBAL\Types\Type;

class EntityGenerator extends BaseEntityGenerator
{
    /**
     * @param array             $fieldMapping
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateFieldMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var ' . $this->getType($fieldMapping['type']);
        
        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';
            
            $column = array();
            if (isset($fieldMapping['columnName'])) {
                $column[] = 'name="' . $fieldMapping['columnName'] . '"';
            }
            
            if (isset($fieldMapping['type'])) {
                $column[] = 'type="' . $fieldMapping['type'] . '"';
            }
            
            if (isset($fieldMapping['length'])) {
                $column[] = 'length=' . $fieldMapping['length'];
            }
            
            if (isset($fieldMapping['precision'])) {
                $column[] = 'precision=' .  $fieldMapping['precision'];
            }
            
            if (isset($fieldMapping['scale'])) {
                $column[] = 'scale=' . $fieldMapping['scale'];
            }
            
            if (isset($fieldMapping['nullable'])) {
                $column[] = 'nullable=' .  var_export($fieldMapping['nullable'], true);
            }
            
            if (isset($fieldMapping['unsigned']) && $fieldMapping['unsigned']) {
                $column[] = 'options={"unsigned"=true}';
            }
            
            if (isset($fieldMapping['options']) && is_array($fieldMapping['options'])) {
                $options = '';
                foreach ($fieldMapping['options'] as $key => $val) {
                    $options .= '"' . $key . '":"' . $val . '",';
                }
                $column[] = 'options={'.trim($options, ',').'}';
            }
            
            if (isset($fieldMapping['columnDefinition'])) {
                $column[] = 'columnDefinition="' . $fieldMapping['columnDefinition'] . '"';
            }
            
            if (isset($fieldMapping['unique'])) {
                $column[] = 'unique=' . var_export($fieldMapping['unique'], true);
            }
            
            $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Column(' . implode(', ', $column) . ')';
            
            if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Id';
                
                if ($generatorType = $this->getIdGeneratorTypeString($metadata->generatorType)) {
                    $lines[] = $this->spaces.' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . $generatorType . '")';
                }
                
                if ($metadata->sequenceGeneratorDefinition) {
                    $sequenceGenerator = array();
                    
                    if (isset($metadata->sequenceGeneratorDefinition['sequenceName'])) {
                        $sequenceGenerator[] = 'sequenceName="' . $metadata->sequenceGeneratorDefinition['sequenceName'] . '"';
                    }
                    
                    if (isset($metadata->sequenceGeneratorDefinition['allocationSize'])) {
                        $sequenceGenerator[] = 'allocationSize=' . $metadata->sequenceGeneratorDefinition['allocationSize'];
                    }
                    
                    if (isset($metadata->sequenceGeneratorDefinition['initialValue'])) {
                        $sequenceGenerator[] = 'initialValue=' . $metadata->sequenceGeneratorDefinition['initialValue'];
                    }
                    
                    $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'SequenceGenerator(' . implode(', ', $sequenceGenerator) . ')';
                }
            }
            
            if (isset($fieldMapping['version']) && $fieldMapping['version']) {
                $lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Version';
            }
        }
        
        $lines[] = $this->spaces . ' */';
        
        return implode("\n", $lines);
    }
    
}
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
            
            /**
             * add Asset NotBlank & Length
             * */
            $comment = isset($fieldMapping['options']) && isset($fieldMapping['options']['comment']) ? $fieldMapping['options']['comment'] : (isset($fieldMapping['columnName']) ? $fieldMapping['columnName'] : '');
            if(!isset($fieldMapping['nullable']) ||(isset($fieldMapping['nullable']) && $fieldMapping['nullable'] == false)){
                $lines[] = $this->spaces . ' * @Assert\NotBlank(message="' . $comment . '不能为空")';
            }
            if (isset($fieldMapping['type']) && $fieldMapping['type'] == 'string' && isset($fieldMapping['length'])) {
                $lines[] = $this->spaces . ' * @Assert\Length(max=' . $fieldMapping['length'] . ', maxMessage="'. $comment . '长度最大为:' .$fieldMapping['length'].'")';
            }
        }
        
        $lines[] = $this->spaces . ' */';
        
        return implode("\n", $lines);
    }
    
    /**
     * @return string
     */
    protected function generateEntityUse()
    {
        if (! $this->generateAnnotations) {
            return '';
        }
        
        return "\n".'use Doctrine\ORM\Mapping as ORM;'."\n".'use Symfony\Component\Validator\Constraints as Assert;'."\n";
    }
    
    /**
     * @param string            $name
     * @param string            $methodName
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    protected function generateLifecycleCallbackMethod($name, $methodName, ClassMetadataInfo $metadata)
    {
        if ($this->hasMethod($methodName, $metadata)) {
            return '';
        }
        
        $this->staticReflection[$metadata->name]['methods'][] = $methodName;
        
        $methodBodys = [
            'prePersist' => '    if($this->getCreateAt() == null){
        $this->setCreateAt(new \DateTime());
        $this->setCreateTime(date(\'Y-m-d H:i:s\'));
    }
    $this->setUpdateAt(new \DateTime());
    $this->setUpdateTime(date(\'Y-m-d H:i:s\'));',
            'preUpdate' => '    $this->setUpdateAt(new \DateTime());
    $this->setUpdateTime(date(\'Y-m-d H:i:s\'));',
        ];
        $replacements = [
            '<name>'        => $this->annotationsPrefix . ucfirst($name),
            '<methodName>'  => $methodName,
            '<methodBody>'  => isset($methodBodys[$methodName]) ? $methodBodys[$methodName] : '',
        ];
        $lifecycleCallbackMethodTemplate = '/**
 * @<name>
 */
public function <methodName>()
{
<spaces>// Add your code here
<methodBody>
}';
        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $lifecycleCallbackMethodTemplate
            );
        
        return $this->prefixCodeWithSpaces($method);
    }
}
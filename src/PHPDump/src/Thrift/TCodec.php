<?php

namespace Minimalism\PHPDump\Thrift;


use Minimalism\PHPDump\Buffer\Buffer;

class TCodec
{
    private $reader;

    public function __construct(Buffer $buffer)
    {
        $this->reader = new TBinaryReader($buffer);
    }

    /**
     * @return ThriftPacket
     */
    public function decode()
    {
        $packet = new ThriftPacket();

        list($packet->type, $packet->name, $packet->seqId) = $this->reader->readMessageBegin();
        $packet->fields = $this->decodeStruct();
        $this->reader->readMessageEnd();

        return $packet;
    }

    private function decodeStruct()
    {
        $reader = $this->reader;

        $reader->readStructBegin();
        $fields = [];
        while (true) {
            list($fieldType, $fieldId) = $reader->readFieldBegin();
            if ($fieldType === TType::STOP) {
                break;
            }
            $fieldVal = $this->decodeField($fieldType);
            $reader->readFieldEnd();

            $fields["#$fieldId"] = $this->buildField($fieldType, $fieldVal);
        }
        $reader->readStructEnd();

        return $fields;
    }

    private function decodeField($fieldType)
    {
        $reader = $this->reader;

        switch ($fieldType) {
            case TType::BOOL:
                $fieldVal = $reader->readBool();
                break;

            case TType::BYTE:
                $fieldVal = $reader->readByte();
                break;

            case TType::DOUBLE:
                $fieldVal = $reader->readDouble();
                break;

            case TType::I16:
                $fieldVal = $reader->readI16();
                break;

            case TType::I32:
                $fieldVal = $reader->readI32();
                break;

            case TType::I64:
                $fieldVal = $reader->readI64();
                break;

            case TType::STRING:
                $fieldVal = $reader->readString();
                break;

            case TType::STRUCT:
                $fieldVal = $this->decodeStruct();
                break;

            case TType::MAP:
                $elemVal = [];
                list($keyType, $valType, $size) = $reader->readMapBegin();
                for ($i = 0; $i < $size; $i++) {
                    $keyVal = $this->decodeField($keyType);
                    $valVal = $this->decodeField($valType);
                    $elemVal[$keyVal] = $valVal;
                }
                $reader->readMapEnd();

                $fieldVal = $this->buildMapField(TType::MAP, $keyType, $valType, $elemVal);
                break;

            case TType::SET:
                $elemVal = [];
                list($elemType, $size) = $reader->readSetBegin();
                for ($i = 0; $i < $size; $i++) {
                    $elemVal[] = $this->decodeField($elemType);
                }
                $reader->readSetEnd();

                $fieldVal = $this->buildListSetField(TType::SET, $elemType, $elemVal);
                break;

            case TType::LST:
                $elemVal = [];
                list($elemType, $size) = $reader->readListBegin();
                for ($i = 0; $i < $size; $i++) {
                    $elemVal[] = $this->decodeField($elemType);
                }
                $reader->readListEnd();

                $fieldVal = $this->buildListSetField(TType::LST, $elemType, $elemVal);
                break;

            case TType::STOP:
            default:
                $fieldVal = null;
                break;
        }

        return $fieldVal;
    }

    private function buildField($fieldType, $fieldVal)
    {
        if (TType::isBasic($fieldType)) {
            $typeName = TType::getName($fieldType);
            return "{$typeName} {$fieldVal}";
        } else {
            return $fieldVal; // buildListSetField 或 buildMapField 结果
        }
    }

    private function buildListSetField($collectionType, $valType, array $listSet)
    {
        $cTypeName = TType::getName($collectionType);
        $vTypeName = TType::getName($valType);
        $typeName = "$cTypeName<$vTypeName>";

        if (TType::isBasic($valType)) {
            $vals = implode(", ", $listSet);
            return "$typeName { $vals }";
        } else {
            return [
                "type" => $typeName,
                "val" => $listSet,
            ];
        }
    }

    private function buildMapField($collectionType, $keyType, $valType, array $map)
    {
        $cTypeName = TType::getName($collectionType);
        $kTypeName = TType::getName($keyType);
        $vTypeName = TType::getName($valType);
        $typeName = "$cTypeName<$kTypeName, $vTypeName>";

        if (TType::isBasic($valType)) {
            $vals = [];
            foreach ($map as $k => $v) {
                $vals[] = "$k: $v";
            }
            $vals = implode(", ", $vals);
            return "$typeName { $vals }";
        } else {
            return [
                "type" => $typeName,
                "val" => $map,
            ];
        }
    }
}
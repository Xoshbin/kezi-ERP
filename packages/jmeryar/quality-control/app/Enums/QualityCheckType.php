<?php

namespace Jmeryar\QualityControl\Enums;

enum QualityCheckType: string
{
    case PassFail = 'pass_fail';
    case Measure = 'measure';
    case TextInput = 'text_input';
    case TakePhoto = 'take_photo';
    case Instructions = 'instructions';

    public function label(): string
    {
        return match ($this) {
            self::PassFail => __('qualitycontrol::enums.check_type.pass_fail'),
            self::Measure => __('qualitycontrol::enums.check_type.measure'),
            self::TextInput => __('qualitycontrol::enums.check_type.text_input'),
            self::TakePhoto => __('qualitycontrol::enums.check_type.take_photo'),
            self::Instructions => __('qualitycontrol::enums.check_type.instructions'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PassFail => 'heroicon-o-check-circle',
            self::Measure => 'heroicon-o-calculator',
            self::TextInput => 'heroicon-o-pencil-square',
            self::TakePhoto => 'heroicon-o-camera',
            self::Instructions => 'heroicon-o-document-text',
        };
    }
}

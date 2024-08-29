@props(["repositories"=>'null',"action"=>'create'])
@php
    $model = "\App\Repository\Form\\$repositories";
	$model= new $model();
	$repositories = $model::formField($action);
@endphp
@foreach($repositories as $repository)
    @switch($repository)
        @case($repository['type']=="select2")
            <x-argon.form-generator-component.select2 :repository="$repository"/>
            @break
        @case($repository['type']=="select")
            <x-argon.form-generator-component.select :repository="$repository"/>
            @break
        @case($repository['type']=="textarea")
            <x-argon.form-generator-component.textarea :repository="$repository"/>
            @break
        @case($repository['type']=="editor")
            <x-argon.form-generator-component.editor :repository="$repository"/>
            @break
        @default
            <x-argon.form-generator-component.input :repository="$repository"/>
    @endswitch
@endforeach

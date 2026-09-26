@extends('errors.layout')

@section('code', '404')
@section('message', $exception->getMessage() ?: 'The requested page does not exist.')

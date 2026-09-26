@extends('errors.layout')

@section('code', '403')
@section('message', $exception->getMessage() ?: 'You are not authorized to perform this action.')

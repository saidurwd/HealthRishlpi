@extends('errors.layout')

@section('code', '503')
@section('message', $exception->getMessage() ?: 'The system is under maintenance. Please try again later.')

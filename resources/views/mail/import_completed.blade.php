subject = "Your import has finished: {{ $import_label }}"
==
Hi {{ $staff_name }},

Your import "{{ $import_label }}" has finished.

{{ $import_summary }}

View the import history: {{ $import_history_url }}
==
Hi {{ $staff_name }},

Your import **{{ $import_label }}** has finished.

{!! nl2br(e($import_summary)) !!}

[View import history]({{ $import_history_url }})

(import (rnrs)
		(lib a))

(display x)

; echo $'(make-boot-file "main.boot" \'("scheme" "petite") "lib/b.ss" "lib/a.ss" "main.ss")' | scheme -q
DOT=dot

%.png: *.gv
	$(DOT) -Tpng $< -v > $@

%.svg: *.gv
	$(DOT) -Tsvg $< -v > $@

.PHONY: all
all:
	make timeline.svg timeline.png

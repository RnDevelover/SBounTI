library(igraph);
#library(jsonlite);
library(RJSONIO);
args = commandArgs(trailingOnly=TRUE)

g<-read.graph(args[1],format="ncol",directed=FALSE);
cl<-max_cliques(g);
#cl
#cl
js<-toJSON(cl, FORCE=true );
#js
cat(js);
#for (i in 1:length(wc)){
#	print(wc[i])
#}

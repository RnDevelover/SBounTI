is.installed <- function(mypkg){
is.element(mypkg, installed.packages()[,1])
} 

if (!is.installed("igraph")){
	install.packages("igraph",repos="http://cran.rstudio.com/")
	if(!is.installed("igraph"))
		quit("no",1,FALSE)
}

if (!is.installed("RJSONIO")){
        install.packages("RJSONIO",repos="http://cran.rstudio.com/")
        if(!is.installed("RJSONIO"))
                quit("no",1,FALSE)
}

quit("no",0,FALSE)


// set the dimensions and margins of the graph
var margin = {top: 20, right: 20, bottom: 300, left: 100},
    width = 960 - margin.left - margin.right,
    height = 500 - margin.top - margin.bottom;


// set the ranges
var x = d3.scaleBand()
          .range([0, width])
          .padding(0.1);
var y = d3.scaleLinear()
          .range([height, 0]);

// append the svg object to the body of the page
// append a 'group' element to 'svg'
// moves the 'group' element to the top left margin
var svg = d3.select("body").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform", 
          "translate(" + margin.left + "," + margin.top + ")");

// get the data
d3.json("files.json").then(function(data) {

  let arrayData = Object.values(data)
    .sort( (a, b) =>  b.TicketCount - a.TicketCount )
    .slice(0,20);
  for ( const i in arrayData ) {
    arrayData[i].LastCommit = d3.max(arrayData[i].Commits.map( function(c) { return c.date}));
    arrayData[i].Tickets = Object.values(arrayData[i].Tickets).sort();
  }
  arrayData = arrayData.sort( (a, b) => b.LastCommit < a.LastCommit ? -1 : (
    b.LastCommit > a.LastCommit ? 1 : 0 ) );
  
  // Scale the range of the data in the domains
  x.domain(arrayData.map(function(d) { return d.File; }));
  y.domain([0, d3.max(arrayData.map( function(d) { return d.TicketCount; }))]);

  var tooltip = d3.select("body")
    .append("div")
    .attr("class", "tooltip")
    .style("opacity", 0);

    let clicked = null;

    // append the rectangles for the bar chart
  svg.selectAll(".bar")
      .data(arrayData)
    .enter().append("rect")
      .attr("class", "bar")
      .attr("x", function(d) { return x(d.File); })
      .attr("width", x.bandwidth())
      .attr("y", function(d) { return y(d.TicketCount); })
      .attr("height", function(d) { return height - y(d.TicketCount); })
      .on('click', function (d, i) {
        if ( clicked == d ) {
          tooltip
            .style("opacity", 0);
          clicked = null;
        } else {
          var html = '<b>' + d.File + '</b><br/><i>Last Commit:</i> ' + d.LastCommit + '<br/>';
          for (const ticket in d.Tickets) {
            html += '<a href="https://phabricator.wikimedia.org/' +
              d.Tickets[ticket] + '" target="_blank">' + d.Tickets[ticket] + '</a><br/>';
          }
          tooltip.html(html)
            .style("left", (d3.event.pageX) + "px")
            .style("top", (d3.event.pageY - 28) + "px")
            .transition()
            .duration(200)
            .style("opacity", 1);
          clicked = d;
        }
       })
      .on('mouseover', function (d, i) {
        d3.select(this).transition()
             .duration('50')
             .attr('opacity', '.85');
         })
      .on('mouseout', function (d, i) {
        d3.select(this).transition()
             .duration('50')
             .attr('opacity', '1')
      })

  // add the x Axis
  svg.append("g")
      .attr("transform", "translate(0," + height + ")")
      .call(d3.axisBottom(x))
      .selectAll("text")	
        .style("text-anchor", "end")
        .attr("dx", "-.8em")
        .attr("dy", ".15em")
        .attr("transform", "rotate(-65)");

  // add the y Axis
  svg.append("g")
      .call(d3.axisLeft(y));

});